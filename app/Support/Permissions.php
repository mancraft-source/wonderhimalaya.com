<?php

namespace App\Support;

final class Permissions
{
    public const CATALOGUE = 'catalogue.manage';

    public const CONTENT = 'content.manage';

    public const CRM_VIEW = 'crm.view';

    public const CRM_MANAGE = 'crm.manage';

    public const CRM_VIEW_ALL = 'crm.view_all';

    public const PAYMENTS = 'payments.manage';

    public const USERS = 'users.manage';

    public const SETTINGS = 'settings.manage';

    public const API = 'api.access';

    /** @return array<string, array<int, string>> role => permissions */
    public static function roles(): array
    {
        return [
            'admin' => self::all(),
            'manager' => [self::CATALOGUE, self::CONTENT, self::CRM_VIEW, self::CRM_MANAGE, self::CRM_VIEW_ALL, self::PAYMENTS, self::SETTINGS, self::API],
            'sales' => [self::CRM_VIEW, self::CRM_MANAGE, self::PAYMENTS],
            'content' => [self::CATALOGUE, self::CONTENT],
        ];
    }

    /** @return array<int, string> */
    public static function all(): array
    {
        return [self::CATALOGUE, self::CONTENT, self::CRM_VIEW, self::CRM_MANAGE, self::CRM_VIEW_ALL, self::PAYMENTS, self::USERS, self::SETTINGS, self::API];
    }
}
