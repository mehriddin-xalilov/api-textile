<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected const DEFAULT_PER_PAGE = 20;

    protected const MAX_PER_PAGE = 200;

    /** ?per_page=50 — admin `limit` → per_page ga queryBuilder tomonidan aylantiriladi. */
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
