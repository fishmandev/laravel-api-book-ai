<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    /**
     * Check authorization for a given permission
     *
     * @throws AuthorizationException
     */
    protected function authorize(string $permission): void
    {
        if (! Gate::allows($permission)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }
}
