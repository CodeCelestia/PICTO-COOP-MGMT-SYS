<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function resolveInternalReturnTo(Request $request): ?string
    {
        $path = $request->input('return_to');

        if (!is_string($path) || $path === '') {
            return null;
        }

        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $path;
    }
}
