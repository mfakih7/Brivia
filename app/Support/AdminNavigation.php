<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Sidebar modules; items are shown only when the server-side ability allows them. */
class AdminNavigation
{
    /** @return list<array{label: string, route: string, pattern: string, ability: ?string, icon: string}> */
    public static function itemsFor(User $user): array
    {
        $items = [
            ['Dashboard', 'admin.dashboard', 'admin.dashboard', null, 'grid'],
            ['Services', 'admin.services.index', 'admin.services.*', 'manage-content', 'layers'],
            ['Packages', 'admin.packages.index', 'admin.packages.*', 'manage-content', 'cube'],
            ['Projects', 'admin.projects.index', 'admin.project*', 'manage-content', 'image'],
            ['Team', 'admin.team.index', 'admin.team.*', 'manage-content', 'users'],
            ['About', 'admin.about.edit', 'admin.about.*', 'manage-content', 'info'],
            ['Homepage', 'admin.homepage.edit', 'admin.homepage.*', 'manage-content', 'home'],
            ['Consultation Types', 'admin.consultation-types.index', 'admin.consultation-types.*', 'manage-content', 'clock'],
            ['Enquiries', 'admin.enquiries.index', 'admin.enquiries.*', 'manage-operations', 'inbox'],
            ['Appointments', 'admin.appointments.index', 'admin.appointments.*', 'manage-operations', 'calendar'],
            ['Email deliveries', 'admin.notifications.index', 'admin.notifications.*', 'manage-operations', 'mail'],
            ['Settings', 'admin.settings.edit', 'admin.settings.*', 'manage-content', 'settings'],
            ['Legal', 'admin.legal.index', 'admin.legal.*', 'manage-content', 'file'],
            ['Staff', 'admin.staff.index', 'admin.staff.*', 'manage-staff', 'shield'],
            ['Audit Log', 'admin.audit.index', 'admin.audit.*', 'view-audit-log', 'list'],
        ];

        return array_values(array_map(
            fn ($item) => ['label' => $item[0], 'route' => $item[1], 'pattern' => $item[2], 'ability' => $item[3], 'icon' => $item[4]],
            array_filter($items, fn ($item) => $item[3] === null || Gate::forUser($user)->allows($item[3])),
        ));
    }
}
