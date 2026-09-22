<?php

namespace App\Http\Controllers;

use App\Mail\PortalRedefinirSenhaMail;
use App\Models\PortalUsuario;
use App\Models\PortalUsuarioAcesso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    // Minutos de validade do link de redefinição de senha (mesmo padrão do broker padrão do Laravel).
    const RESET_EXPIRE_MINUTOS = 60;

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

        PortalUsuarioAcesso::create([
            'portal_usuario_id' => $portalUsuario->id,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        $request->session()->regenerate();

        if ($portalUsuario->deve_trocar_senha) {
            return redirect()->route('portal.trocar-senha');
        }

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('portal')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function showTrocarSenha(): View
    {
        return view('portal.trocar-senha');
    }

    public function trocarSenha(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A senha deve ter ao menos 6 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ]);

        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();

        $portalUsuario->update([
            'password' => Hash::make($request->password),
            'deve_trocar_senha' => false,
        ]);

        return redirect()->route('portal.dashboard')->with('success', 'Senha atualizada com sucesso.');
    }

    public function showForgotPassword(): View
    {
        return view('portal.auth.esqueci-senha');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Resposta genérica independente de o e-mail existir ou não — evita
        // que a tela vire um oráculo pra descobrir quais e-mails têm acesso ao portal.
        $mensagemGenerica = 'Se houver um acesso cadastrado com este e-mail, enviamos um link de redefinição.';

        $portalUsuario = PortalUsuario::where('email', $request->email)->where('ativo', true)->first();

        if (! $portalUsuario) {
            return back()->with('success', $mensagemGenerica);
        }

        $ultimoPedido = DB::table('portal_password_reset_tokens')->where('email', $request->email)->first();

        if ($ultimoPedido && now()->diffInSeconds($ultimoPedido->created_at) < 60) {
            return back()->with('success', $mensagemGenerica);
        }

        $token = Str::random(64);

        DB::table('portal_password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()],
        );

        Mail::to($portalUsuario->email, $portalUsuario->nome)->send(new PortalRedefinirSenhaMail(
            nomeUsuario: $portalUsuario->nome,
            linkRedefinir: route('portal.password.reset', ['token' => $token, 'email' => $request->email]),
        ));

        return back()->with('success', $mensagemGenerica);
    }

    public function showResetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email', '');
        $registro = DB::table('portal_password_reset_tokens')->where('email', $email)->first();

        if (! $registro || ! Hash::check($token, $registro->token) || now()->diffInMinutes($registro->created_at) > self::RESET_EXPIRE_MINUTOS) {
            return redirect()->route('portal.password.request')->withErrors([
                'email' => 'Este link de redefinição é inválido ou expirou. Solicite um novo.',
            ]);
        }

        return view('portal.auth.redefinir-senha', ['token' => $token, 'email' => $email]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A senha deve ter ao menos 6 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ]);

        $registro = DB::table('portal_password_reset_tokens')->where('email', $request->email)->first();

        if (! $registro || ! Hash::check($request->token, $registro->token) || now()->diffInMinutes($registro->created_at) > self::RESET_EXPIRE_MINUTOS) {
            return redirect()->route('portal.password.request')->withErrors([
                'email' => 'Este link de redefinição é inválido ou expirou. Solicite um novo.',
            ]);
        }

        $portalUsuario = PortalUsuario::where('email', $request->email)->where('ativo', true)->first();

        if (! $portalUsuario) {
            return redirect()->route('portal.password.request')->withErrors([
                'email' => 'Não encontramos um acesso ativo para este e-mail.',
            ]);
        }

        $portalUsuario->update([
            'password' => Hash::make($request->password),
            'deve_trocar_senha' => false,
        ]);

        DB::table('portal_password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('portal.login')->with('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
    }
}
