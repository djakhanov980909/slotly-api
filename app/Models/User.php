<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $phone
 * @property Role $role
 */
#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'role' => Role::class,
        ];

    }

    /** @return BelongsToMany<Service, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_specialist', 'specialist_id', 'service_id');
    }

    /** @return HasMany<WorkingHour, $this> */
    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class, 'specialist_id');
    }

    /** @return HasMany<TimeOff, $this> */
    public function timeOffs(): HasMany
    {
        return $this->hasMany(TimeOff::class, 'specialist_id');
    }

    /** @return HasMany<Booking, $this> */
    public function clientBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    public function specialistBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'specialist_id');
    }
}
