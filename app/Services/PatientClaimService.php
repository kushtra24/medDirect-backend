<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PatientClaimService
{
    /**
     * Link unclaimed patient records to the user when identity fields match.
     *
     * @return Collection<int, Patient>
     */
    public function claimForUser(User $user): Collection
    {
        $candidates = $this->findUnclaimedMatches($user);

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $now = now();
        Patient::query()
            ->whereIn('id', $candidates->pluck('id'))
            ->whereNull('user_id')
            ->update([
                'user_id' => $user->id,
                'claimed_at' => $now,
            ]);

        return Patient::query()
            ->whereIn('id', $candidates->pluck('id'))
            ->get();
    }

    /**
     * @return Collection<int, Patient>
     */
    public function findUnclaimedMatches(User $user): Collection
    {
        $email = $user->email ? Str::lower($user->email) : null;
        $name = Str::lower(trim($user->name));
        $dob = $user->date_of_birth;

        if ($email === null && ($dob === null || $name === '')) {
            return collect();
        }

        return Patient::query()
            ->whereNull('user_id')
            ->where(function ($outer) use ($email, $name, $dob) {
                if ($email !== null) {
                    $outer->orWhereRaw('LOWER(email) = ?', [$email]);
                }

                if ($dob !== null && $name !== '') {
                    $outer->orWhere(function ($q) use ($name, $dob) {
                        $q->whereDate('date_of_birth', $dob)
                            ->whereRaw(
                                "LOWER(CONCAT(first_name, ' ', surname)) = ?",
                                [$name]
                            );
                    });
                }
            })
            ->get();
    }

    /**
     * Find the patient-portal account whose identity exactly matches the record a clinic
     * is about to create, so the two can be linked via `patients.user_id`.
     *
     * All four fields (national ID, first name, surname, date of birth) must be supplied
     * and must all match — this is intentionally stricter than {@see findUnclaimedMatches}
     * so staff data entry never links to the wrong person on a partial/common-name match.
     *
     * @param  array{first_name?: ?string, surname?: ?string, date_of_birth?: ?string, national_id?: ?string}  $identity
     */
    public function findExactMatchUser(array $identity): ?User
    {
        $firstName = trim((string) ($identity['first_name'] ?? ''));
        $surname = trim((string) ($identity['surname'] ?? ''));
        $dob = $identity['date_of_birth'] ?? null;
        $nationalId = trim((string) ($identity['national_id'] ?? ''));

        if ($firstName === '' || $surname === '' || $dob === null || $nationalId === '') {
            return null;
        }

        $name = Str::lower($firstName.' '.$surname);

        return User::query()
            ->where('role', User::ROLE_PATIENT)
            ->whereRaw('LOWER(national_id) = ?', [Str::lower($nationalId)])
            ->whereDate('date_of_birth', $dob)
            ->whereRaw('LOWER(name) = ?', [$name])
            ->first();
    }

    /**
     * Guest lookup by display name and access PIN.
     */
    public function findByNameAndPin(string $fullName, string $pin): ?Patient
    {
        $normalized = Str::lower(preg_replace('/\s+/', ' ', trim($fullName)) ?? '');

        if ($normalized === '' || $pin === '') {
            return null;
        }

        $patients = Patient::query()
            ->whereNotNull('access_pin')
            ->get();

        foreach ($patients as $patient) {
            $patientName = Str::lower($patient->fullName());
            if ($patientName !== $normalized) {
                continue;
            }

            if (! hash_equals((string) $patient->access_pin, $pin)) {
                continue;
            }

            return $patient;
        }

        return null;
    }
}
