<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;

final class StaffAwareLoginResponse implements LoginResponse, TwoFactorLoginResponse
{
    private const PERMISSION = 'dashboard.read';

    /**
     * @param  Request  $request
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();
        $target = $user instanceof User && $user->can(self::PERMISSION)
            ? route('admin.dashboard')
            : route('home');

        return redirect()->intended($target);
    }
}
