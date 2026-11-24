<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Audit\Services\ActivityLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Log the user out of the application.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            app(ActivityLogService::class)->log(
                action: 'logout',
                description: 'User melakukan logout dari sistem',
                subject: $user,
                userId: $user->id
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil logout.');
    }
}
