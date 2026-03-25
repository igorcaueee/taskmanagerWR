<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',
        'cargo',
    ];

    /**
     * Get the password for the user (Auth expects this).
     */
    public function getAuthPassword(): string
    {
        return (string) $this->senha;
    }

    /**
     * Return the password column name used by the framework.
     */
    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    /**
     * Provide a `name` attribute for compatibility with templates.
     */
    public function getNameAttribute(): ?string
    {
        return $this->nome;
    }

    /**
     * Hide sensitive attributes when serializing.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',
    ];
}
