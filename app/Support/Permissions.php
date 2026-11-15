<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * The permission catalogue — the one place that lists every admin permission.
 *
 * Section permissions are named "{section}.{ability}" (e.g. contacts.view, contacts.update);
 * special permissions cover cross-cutting rights (exports, settings, users…). Policies, the
 * Roles & permissions screen, the seeder and the data migration all read from here.
 * Admins (role "admin") pass every check via Gate::before.
 */
final class Permissions
{
    public const ADMIN_ROLE = 'admin';

    public const VIEW = 'view';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    /** Matrix columns: ability => label. */
    public const ABILITIES = [
        self::VIEW => 'View',
        self::CREATE => 'Create',
        self::UPDATE => 'Edit',
        self::DELETE => 'Delete',
    ];

    /** Areas the sections are grouped under (match the admin navigation groups). */
    public const GROUPS = ['CRM', 'Sales', 'Catalogue', 'Content', 'SEO'];

    /**
     * Admin sections: key => label, group and (optionally) the abilities that apply.
     *
     * @var array<string, array{label: string, group: string, abilities?: array<int, string>}>
     */
    public const SECTIONS = [
        'leads' => ['label' => 'Leads', 'group' => 'CRM'],
        'contacts' => ['label' => 'Contacts', 'group' => 'CRM'],
        'companies' => ['label' => 'Partners (companies)', 'group' => 'CRM'],
        'tasks' => ['label' => 'Tasks', 'group' => 'CRM'],
        'quotes' => ['label' => 'Quotes', 'group' => 'Sales'],
        'bookings' => ['label' => 'Bookings', 'group' => 'Sales'],
        'payments' => ['label' => 'Payments', 'group' => 'Sales'],
        'trips' => ['label' => 'Trips', 'group' => 'Catalogue'],
        'departures' => ['label' => 'Departures', 'group' => 'Catalogue'],
        'destinations' => ['label' => 'Destinations', 'group' => 'Catalogue'],
        'activities' => ['label' => 'Activities', 'group' => 'Catalogue'],
        'difficulties' => ['label' => 'Difficulties', 'group' => 'Catalogue'],
        'tiers' => ['label' => 'Tiers', 'group' => 'Catalogue'],
        'tags' => ['label' => 'Tags', 'group' => 'Catalogue'],
        'pages' => ['label' => 'Pages & page content', 'group' => 'Content'],
        'posts' => ['label' => 'Blog posts', 'group' => 'Content'],
        'post_categories' => ['label' => 'Post categories', 'group' => 'Content'],
        'team_members' => ['label' => 'Team members', 'group' => 'Content'],
        'testimonials' => ['label' => 'Testimonials', 'group' => 'Content'],
        'menus' => ['label' => 'Menus', 'group' => 'Content'],
        'media' => ['label' => 'Media library', 'group' => 'Content', 'abilities' => [self::VIEW, self::UPDATE, self::DELETE]],
        'redirects' => ['label' => 'Redirects', 'group' => 'SEO'],
        'not_found_logs' => ['label' => '404 monitor', 'group' => 'SEO'],
    ];

    public const LEADS_VIEW_ALL = 'leads.view_all';

    public const EXPORTS = 'exports.run';

    public const SEO = 'seo.manage';

    public const SETTINGS = 'settings.manage';

    public const SYSTEM = 'system.view';

    public const ACTIVITY_LOG = 'activity_log.view';

    public const USERS = 'users.manage';

    public const API = 'api.access';

    /**
     * Special permissions: name => label and description.
     *
     * @var array<string, array{label: string, description: string}>
     */
    public const SPECIAL = [
        self::LEADS_VIEW_ALL => ['label' => 'See all leads', 'description' => 'See and work every lead (not only their own and unassigned ones), every follow-up and task on the dashboard, and hand leads to other people.'],
        self::EXPORTS => ['label' => 'Export', 'description' => 'Download CSV / XLSX exports of the sections they can view.'],
        self::SEO => ['label' => 'SEO settings', 'description' => 'SEO settings and the SEO overview.'],
        self::SETTINGS => ['label' => 'Site settings', 'description' => 'Site settings and notification settings.'],
        self::SYSTEM => ['label' => 'System page', 'description' => 'System status, errors and maintenance actions.'],
        self::ACTIVITY_LOG => ['label' => 'Activity log', 'description' => 'Read the activity log of changes across the admin.'],
        self::USERS => ['label' => 'Users & roles', 'description' => 'Manage staff accounts and roles. They can only grant permissions they have themselves.'],
        self::API => ['label' => 'API access', 'description' => 'Create API tokens and read the API documentation.'],
    ];

    /** "{section}.{ability}", e.g. Permissions::for('contacts', 'update') = "contacts.update". */
    public static function for(string $section, string $ability): string
    {
        if (! in_array($ability, self::abilitiesFor($section), true)) {
            throw new InvalidArgumentException("Unknown permission {$section}.{$ability}.");
        }

        return $section.'.'.$ability;
    }

    /** @return array<int, string> */
    public static function abilitiesFor(string $section): array
    {
        if (! isset(self::SECTIONS[$section])) {
            throw new InvalidArgumentException("Unknown permission section {$section}.");
        }

        return self::SECTIONS[$section]['abilities'] ?? array_keys(self::ABILITIES);
    }

    /** @return array<int, string> Every section permission (contacts.view, contacts.create…). */
    public static function sectionPermissions(): array
    {
        $permissions = [];

        foreach (array_keys(self::SECTIONS) as $section) {
            foreach (self::abilitiesFor($section) as $ability) {
                $permissions[] = $section.'.'.$ability;
            }
        }

        return $permissions;
    }

    /** @return array<int, string> */
    public static function specialPermissions(): array
    {
        return array_keys(self::SPECIAL);
    }

    /** @return array<int, string> Every permission in the catalogue. */
    public static function all(): array
    {
        return [...self::sectionPermissions(), ...self::specialPermissions()];
    }

    /** @return array<string, array<string, string>> group => [section => label] */
    public static function groupedSections(): array
    {
        $groups = array_fill_keys(self::GROUPS, []);

        foreach (self::SECTIONS as $section => $definition) {
            $groups[$definition['group']][$section] = $definition['label'];
        }

        return array_filter($groups);
    }

    /**
     * Default roles (seeded on fresh installs; the granular equivalent of the original
     * coarse roles, see the migrate_to_granular_permissions migration).
     *
     * @return array<string, array<int, string>> role => permissions
     */
    public static function roles(): array
    {
        $crm = ['leads', 'contacts', 'companies', 'tasks', 'quotes', 'bookings'];
        $catalogue = ['trips', 'departures', 'destinations', 'activities', 'difficulties', 'tiers', 'tags'];
        $content = ['pages', 'posts', 'post_categories', 'team_members', 'testimonials', 'menus', 'media', 'redirects', 'not_found_logs'];

        return [
            self::ADMIN_ROLE => self::all(),
            'manager' => array_values(array_diff(self::all(), [self::USERS])),
            'sales' => [
                ...array_values(array_diff(self::fullAccess([...$crm, 'payments']), ['leads.delete'])),
                self::EXPORTS,
            ],
            'content' => self::fullAccess([...$catalogue, ...$content]),
        ];
    }

    /**
     * Every ability of the given sections.
     *
     * @param  array<int, string>  $sections
     * @return array<int, string>
     */
    public static function fullAccess(array $sections): array
    {
        return array_merge(...array_map(
            fn (string $section): array => array_map(fn (string $ability): string => $section.'.'.$ability, self::abilitiesFor($section)),
            $sections,
        ));
    }

    /**
     * Keep only catalogue permissions, and add "view" to any section where another ability is granted
     * (editing or deleting what you cannot see makes no sense).
     *
     * @param  iterable<int, string>  $permissions
     * @return array<int, string>
     */
    public static function normalize(iterable $permissions): array
    {
        $permissions = array_values(array_intersect(array_unique([...$permissions]), self::all()));

        foreach ($permissions as $permission) {
            [$section] = explode('.', $permission, 2);

            if (isset(self::SECTIONS[$section])) {
                $permissions[] = $section.'.'.self::VIEW;
            }
        }

        return array_values(array_intersect(self::all(), $permissions));
    }

    /** Human label, e.g. "Contacts: Edit" or "Export". */
    public static function label(string $permission): string
    {
        if (isset(self::SPECIAL[$permission])) {
            return self::SPECIAL[$permission]['label'];
        }

        [$section, $ability] = array_pad(explode('.', $permission, 2), 2, '');

        if (isset(self::SECTIONS[$section], self::ABILITIES[$ability])) {
            return self::SECTIONS[$section]['label'].': '.self::ABILITIES[$ability];
        }

        return $permission;
    }

    /**
     * One-line summary of what a set of permissions allows,
     * e.g. "Full access: Leads · View & edit: Contacts · View: Trips · Special: Export".
     *
     * @param  iterable<int, string>  $permissions
     */
    public static function summarize(iterable $permissions): string
    {
        $permissions = self::normalize($permissions);
        $combinations = [];

        foreach (self::SECTIONS as $section => $definition) {
            $abilities = array_values(array_filter(
                self::abilitiesFor($section),
                fn (string $ability): bool => in_array($section.'.'.$ability, $permissions, true),
            ));

            if ($abilities === []) {
                continue;
            }

            $combinations[self::abilitiesLabel($section, $abilities)][] = $definition['label'];
        }

        uksort($combinations, fn (string $a, string $b): int => self::combinationRank($a) <=> self::combinationRank($b));

        $parts = [];

        foreach ($combinations as $label => $sections) {
            $parts[] = $label.': '.implode(', ', $sections);
        }

        $special = array_map(self::label(...), array_values(array_intersect(self::specialPermissions(), $permissions)));

        if ($special !== []) {
            $parts[] = 'Special: '.implode(', ', $special);
        }

        return $parts === [] ? 'No access' : implode(' · ', $parts);
    }

    /** @param  array<int, string>  $abilities */
    private static function abilitiesLabel(string $section, array $abilities): string
    {
        if ($abilities === self::abilitiesFor($section)) {
            return 'Full access';
        }

        $words = array_map(fn (string $ability): string => self::ABILITIES[$ability], $abilities);
        $last = array_pop($words);
        $label = $words === [] ? $last : implode(', ', $words).' & '.$last;

        return ucfirst(strtolower($label));
    }

    private static function combinationRank(string $label): int
    {
        return $label === 'Full access' ? 0 : 10 - substr_count($label, ',') - substr_count($label, '&');
    }
}
