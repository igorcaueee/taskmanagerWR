<?php

namespace App\Services;

use App\Models\LiberacaoAcessoExterno;
use App\Models\RedePermitida;
use App\Models\Usuario;

class AcessoRedeService
{
    public function acessoPermitido(Usuario $usuario, string $ip): bool
    {
        if ($usuario->isDiretor()) {
            return true;
        }

        if ($this->ipPermitido($ip)) {
            return true;
        }

        return LiberacaoAcessoExterno::where('usuario_id', $usuario->id)
            ->where('ativo', true)
            ->where(function ($q) {
                $q->whereNull('expira_em')
                  ->orWhere('expira_em', '>', now());
            })
            ->exists();
    }

    public function ipPermitido(string $ip): bool
    {
        $redes = RedePermitida::where('ativo', true)->pluck('cidr');

        foreach ($redes as $cidr) {
            if ($this->ipNaFaixa($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipNaFaixa(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            $cidr .= '/32';
        }

        [$subnet, $bits] = explode('/', $cidr);

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits == 0 ? 0 : (~0 << (32 - (int) $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
