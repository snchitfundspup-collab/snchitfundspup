<?php

namespace App\Http\Requests\Portal;

use App\Models\Customer;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * A customer signing in with their phone number and password.
 */
class CustomerLoginRequest extends FormRequest
{
    /**
     * Failed attempts allowed before the phone number + IP is locked out.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * The customers (one, or several sharing the phone) whose phone number
     * and password match, throttling repeated failures.
     *
     * @return Collection<int, Customer>
     *
     * @throws ValidationException
     */
    public function customers(): Collection
    {
        $this->ensureIsNotRateLimited();

        $customers = Customer::forLogin($this->string('phone'), $this->string('password'));

        if ($customers->isEmpty()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'phone' => 'The phone number or password is not correct.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $customers;
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'phone' => 'Too many tries. Please try again in '.ceil($seconds / 60).' minute(s).',
        ]);
    }

    private function throttleKey(): string
    {
        return 'customer-login|'.Customer::phoneDigits($this->string('phone')).'|'.$this->ip();
    }
}
