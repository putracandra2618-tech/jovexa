<?php

namespace App\Support;

/**
 * What the shared admin topbar search should search on one particular screen.
 *
 * Screens that do not own a list, such as the dashboard and the detail or form
 * pages, have no target and hide the search box instead.
 */
class AdminSearchTarget
{
    public function __construct(
        public readonly string $action,
        public readonly string $placeholder,
        public readonly ?string $filter = null,
    ) {}
}
