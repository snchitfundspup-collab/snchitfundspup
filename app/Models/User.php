<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'is_partner', 'can_view_usage'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_partner' => 'boolean',
            'can_view_usage' => 'boolean',
        ];
    }

    /**
     * This person's signature as a data URI for receipts, bills and PDFs
     * (resources/signatures/{username}.png), or null when there is none.
     * Kept out of public/ so it cannot be downloaded by URL.
     */
    public function signatureDataUri(): ?string
    {
        static $cache = [];

        $username = basename(strtolower((string) $this->username));

        if ($username === '') {
            return null;
        }

        return $cache[$username] ??= (function () use ($username) {
            $path = resource_path('signatures/'.$username.'.png');

            return is_file($path) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($path)) : null;
        })();
    }

    /**
     * Partners share the business spending equally.
     *
     * @param  Builder<User>  $query
     */
    public function scopePartners(Builder $query): void
    {
        $query->where('is_partner', true)->orderBy('name');
    }
}
