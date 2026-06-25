<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'role_id',
        'poli_id',
        'nama',
        'username',
        'password',
        'email',
        'telepon',
        'avatar',
        'status',
        'last_login',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'created_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    public function roleKode(): ?string
    {
        return $this->role?->kode;
    }
}
