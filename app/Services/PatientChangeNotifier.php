<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;

class PatientChangeNotifier
{
    /**
     * Maps a User profile field to the corresponding Patient field name.
     *
     * @var array<string, string>
     */
    private const FIELD_MAP = [
        'date_of_birth' => 'date_of_birth',
        'blood_type' => 'blood_group',
        'phone' => 'phone',
        'national_id' => 'national_id',
    ];

    /**
     * After a patient-portal user saves their own profile, flag every clinic that holds a
     * linked Patient record whose stored value now disagrees with what the patient just
     * entered. Clinics keep their own independent copy of patient data (no cross-clinic
     * sharing) — this only raises a notice for staff to review and update manually.
     */
    public function notifyForUser(User $user): void
    {
        $changedUserFields = array_values(array_filter(
            array_keys(self::FIELD_MAP),
            fn (string $field) => $user->wasChanged($field)
        ));

        if ($changedUserFields === []) {
            return;
        }

        $patients = Patient::query()->where('user_id', $user->id)->get();

        foreach ($patients as $patient) {
            $changes = $this->divergentFields($user, $patient, $changedUserFields);

            if ($changes === []) {
                continue;
            }

            $patient->updateNotices()->create([
                'organization_id' => $patient->organization_id,
                'user_id' => $user->id,
                'changed_fields' => $changes,
            ]);
        }
    }

    /**
     * @param  list<string>  $userFields
     * @return array<string, array{old: ?string, new: ?string}>
     */
    private function divergentFields(User $user, Patient $patient, array $userFields): array
    {
        $changes = [];

        foreach ($userFields as $userField) {
            $patientField = self::FIELD_MAP[$userField];

            $newValue = $userField === 'date_of_birth'
                ? $user->date_of_birth?->format('Y-m-d')
                : $user->{$userField};

            $oldValue = $patientField === 'date_of_birth'
                ? $patient->date_of_birth?->format('Y-m-d')
                : $patient->{$patientField};

            if ($this->normalized($newValue) === $this->normalized($oldValue)) {
                continue;
            }

            $changes[$patientField] = ['old' => $oldValue, 'new' => $newValue];
        }

        return $changes;
    }

    private function normalized(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_strtolower($value);
    }
}
