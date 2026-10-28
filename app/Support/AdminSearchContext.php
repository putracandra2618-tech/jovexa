<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves the dataset behind the shared admin topbar search box.
 *
 * The topbar is rendered on every admin screen, so it needs to know which list,
 * if any, the current screen owns. Keeping the map in one place stops the
 * placeholder and the form action from drifting apart per view.
 */
class AdminSearchContext
{
    /**
     * The searchable admin screens, keyed by nothing and matched by route pattern.
     *
     * Ordered most specific first because the lookup stops at the first match.
     * `filter` names the secondary dropdown that a refined search must carry
     * along, so narrowing the keyword never drops the active filter. Screens
     * whose rows are pinned to a single status, like the revision and history
     * queues, have no such dropdown and use null.
     *
     * @var list<array{pattern: string, action: string, placeholder: string, filter: ?string}>
     */
    private const TARGETS = [
        [
            'pattern' => 'worker.orders.history',
            'action' => 'worker.orders.history',
            'placeholder' => 'Cari riwayat pesanan saya',
            'filter' => null,
        ],
        [
            'pattern' => 'worker.orders.index',
            'action' => 'worker.orders.index',
            'placeholder' => 'Cari pesanan saya',
            'filter' => 'status',
        ],
        [
            'pattern' => 'worker.queue.index',
            'action' => 'worker.queue.index',
            'placeholder' => 'Cari antrean pesanan',
            'filter' => null,
        ],
        [
            'pattern' => 'admin.orders.revision',
            'action' => 'admin.orders.revision',
            'placeholder' => 'Cari pesanan yang perlu direvisi',
            'filter' => null,
        ],
        [
            'pattern' => 'admin.orders.history',
            'action' => 'admin.orders.history',
            'placeholder' => 'Cari riwayat pesanan',
            'filter' => null,
        ],
        [
            'pattern' => 'admin.orders.index',
            'action' => 'admin.orders.index',
            'placeholder' => 'Cari nomor pesanan, nama, atau email customer',
            'filter' => 'status',
        ],
        [
            'pattern' => 'admin.services.index',
            'action' => 'admin.services.index',
            'placeholder' => 'Cari nama, slug, atau deskripsi jasa',
            'filter' => 'status',
        ],
        [
            'pattern' => 'admin.users.index',
            'action' => 'admin.users.index',
            'placeholder' => 'Cari nama atau email pengguna',
            'filter' => 'role',
        ],
    ];

    /**
     * Resolve the search target for the current screen, if it has one.
     */
    public static function for(Request $request): ?AdminSearchTarget
    {
        foreach (self::TARGETS as $target) {
            if (! $request->routeIs($target['pattern'])) {
                continue;
            }

            return new AdminSearchTarget(
                action: $target['action'],
                placeholder: $target['placeholder'],
                filter: $target['filter'],
            );
        }

        return null;
    }
}
