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

    public const CAN_CREATE_ORDER = 'canCreateOrder';

    public const CAN_MANAGE_STAFF = 'canManageStaff';

    public const CAN_EDIT_ORGANIZATION_PROFILE = 'canEditOrganizationProfile';

    /**
     * Abilities every new staff member starts with, without the owner having to configure
     * anything — front-desk work (registering who's here and why) is core to being staff
     * at all, unlike the more sensitive abilities below which default off.
     *
     * @var list<string>
     */
    private const DEFAULT_GRANTED = [
        self::CAN_CREATE_PATIENT,
        self::CAN_CREATE_ORDER,
    ];

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
            self::CAN_CREATE_ORDER,
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
     * The starting point for a new staff member before any explicit choices are stored —
     * everything off except {@see DEFAULT_GRANTED}.
     *
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        $base = self::allFalse();
        foreach (self::DEFAULT_GRANTED as $key) {
            $base[$key] = true;
        }

        return $base;
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

        $base = self::defaults();
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
