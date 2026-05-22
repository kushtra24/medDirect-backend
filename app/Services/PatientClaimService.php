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

            if (! \Illuminate\Support\Facades\Hash::check($pin, (string) $patient->access_pin)) {
                continue;
            }

            return $patient;
        }

        return null;
    }
}
