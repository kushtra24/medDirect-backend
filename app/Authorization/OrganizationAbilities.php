<?php

namespace App\Authorization;

/**
 * Section abilities for clinic/lab staff. Owners implicitly have all abilities.
 */
final class OrganizationAbilities
{
    public const CAN_CREATE_PATIENT = 'canCreatePatient';

    public const CAN_EDIT_PATIENT = 'canEditPatient';

    public const CAN_VIEW_PATIENTS = 'canViewPatients';

    public const CAN_EDIT_LAB_RESULTS = 'canEditLabResults';

    public const CAN_CREATE_ADD_RESULTS = 'canCreateAddResults';

    public const CAN_CREATE_DOCTORS_NOTE = 'canCreateDoctorsNote';

    public const CAN_MANAGE_STAFF = 'canManageStaff';

    public const CAN_EDIT_ORGANIZATION_PROFILE = 'canEditOrganizationProfile';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::CAN_CREATE_PATIENT,
            self::CAN_EDIT_PATIENT,
            self::CAN_VIEW_PATIENTS,
            self::CAN_EDIT_LAB_RESULTS,
            self::CAN_CREATE_ADD_RESULTS,
            self::CAN_CREATE_DOCTORS_NOTE,
            self::CAN_MANAGE_STAFF,
            self::CAN_EDIT_ORGANIZATION_PROFILE,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function allTrue(): array
    {
        return array_fill_keys(self::keys(), true);
    }

    /**
     * @return array<string, bool>
     */
    public static function allFalse(): array
    {
        return array_fill_keys(self::keys(), false);
    }

    /**
     * @param  array<string, bool>|null  $stored
     * @return array<string, bool>
     */
    public static function normalize(?array $stored, bool $isOwner): array
    {
        if ($isOwner) {
            return self::allTrue();
        }

        $base = self::allFalse();
        if ($stored === null) {
            return $base;
        }

        foreach (self::keys() as $key) {
            if (array_key_exists($key, $stored)) {
                $base[$key] = (bool) $stored[$key];
            }
        }

        return $base;
    }
}
