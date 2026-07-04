<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function currentWorkspace(): ?Workspace
    {
        $workspaceId = session('workspace_id');

        return $workspaceId
            ? $this->workspaces()->whereKey($workspaceId)->first()
            : $this->workspaces()->first();
    }

    public function canInWorkspace(string $permission, Workspace $workspace): bool
    {
        $role = $this->workspaces()->whereKey($workspace->id)->first()?->pivot->role;

        return match ($role) {
            'Owner' => true,
            'Admin' => in_array($permission, ['manage_workspace', 'manage_users', 'manage_leads', 'manage_customers', 'manage_deals', 'manage_tasks', 'view_reports', 'manage_settings'], true),
            'Manager' => in_array($permission, ['manage_leads', 'manage_customers', 'manage_deals', 'manage_tasks', 'view_reports'], true),
            'Sales Executive' => in_array($permission, ['manage_leads', 'manage_customers', 'manage_deals', 'manage_tasks'], true),
            'Viewer' => $permission === 'view_reports',
            default => false,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
