<?php

namespace App\Http\Controllers;

use App\Models\PortalUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('portal')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'ativo' => true,
        ];

        if (! Auth::guard('portal')->attempt($credentials)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Usuário ou senha inválidos.']);
        }

        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $portalUsuario->update(['ultimo_acesso' => now()]);

        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('portal')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
