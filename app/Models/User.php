<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasTenants
{

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user');
    }

    public function canAccessTenant($tenant): bool
    {
        return $this->tenants()->where('tenant_id', $tenant->id)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Ajusta según tus necesidades (por ejemplo, verifica roles)
    }

    public function getTenants(Panel $panel): array|\Illuminate\Support\Collection
    {
        return $this->tenants;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\Stancl\Tenancy\Database\Models\Tenant::class, 'tenant_id');
    }
}
