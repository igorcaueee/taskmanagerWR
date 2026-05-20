<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'cpfcnpj' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $cpfcnpj = preg_replace('/\D/', '', $request->cpfcnpj);

        $cliente = Cliente::whereRaw(
            "REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '') = ?",
            [$cpfcnpj]
        )
            ->where('portal_ativo', true)
            ->first();

        if (! $cliente || ! Hash::check($request->password, $cliente->senha_portal)) {
            return back()
                ->withInput($request->only('cpfcnpj'))
                ->withErrors(['cpfcnpj' => 'CNPJ/CPF ou senha inválidos.']);
        }

        Auth::guard('portal')->login($cliente);

        $cliente->update(['portal_ultimo_acesso' => now()]);

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
