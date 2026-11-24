<?php

namespace App\Livewire\Auth;

use App\Domains\Audit\Services\ActivityLogService;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Login')]
class Login extends Component
{
    /**
     * Akun demo terdaftar untuk pengujian hak akses dan role.
     *
     * @var array<string, array<string, string>>
     */
    public const DEMO_ROLES = [
        'Owner' => [
            'name' => 'Owner Natural Gypsum',
            'email' => 'owner@naturalgypsum.test',
            'icon' => 'bi-shield-shaded',
            'badge' => 'bg-danger-subtle text-danger border-danger-subtle',
            'btn_variant' => 'btn-outline-danger',
            'badge_name' => 'Owner',
            'description' => 'Akses penuh ke seluruh sistem & modul master.',
        ],
        'Manager' => [
            'name' => 'Manager Operasional',
            'email' => 'manager@naturalgypsum.test',
            'icon' => 'bi-person-badge-fill',
            'badge' => 'bg-primary-subtle text-primary border-primary-subtle',
            'btn_variant' => 'btn-outline-primary',
            'badge_name' => 'Manager',
            'description' => 'Approval transaksi, audit, dan laporan operasional.',
        ],
        'Staff Penjualan' => [
            'name' => 'Staff Penjualan (Kasir)',
            'email' => 'penjualan@naturalgypsum.test',
            'icon' => 'bi-cart-check-fill',
            'badge' => 'bg-success-subtle text-success border-success-subtle',
            'btn_variant' => 'btn-outline-success',
            'badge_name' => 'Penjualan',
            'description' => 'Faktur penjualan, kasir POS, dan retur pelanggan.',
        ],
        'Staff Gudang' => [
            'name' => 'Staff Gudang (Logistik)',
            'email' => 'gudang@naturalgypsum.test',
            'icon' => 'bi-boxes',
            'badge' => 'bg-warning-subtle text-warning border-warning-subtle',
            'btn_variant' => 'btn-outline-warning',
            'badge_name' => 'Gudang',
            'description' => 'Stok mutasi, transfer gudang, & stock opname.',
        ],
        'Staff Produksi' => [
            'name' => 'Staff Produksi',
            'email' => 'produksi@naturalgypsum.test',
            'icon' => 'bi-gear-wide-connected',
            'badge' => 'bg-info-subtle text-info border-info-subtle',
            'btn_variant' => 'btn-outline-info',
            'badge_name' => 'Produksi',
            'description' => 'Order produksi barang & kartu stok bahan.',
        ],
    ];

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Rules for validation.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Authenticate the user.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        app(ActivityLogService::class)->log(
            action: 'login',
            description: 'User berhasil masuk ke dalam sistem',
            subject: Auth::user()
        );

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /**
     * Login secara instan berdasarkan role yang dipilih (1-Click Quick Login).
     */
    public function quickLogin(string $role): void
    {
        if (! array_key_exists($role, self::DEMO_ROLES)) {
            return;
        }

        $userData = self::DEMO_ROLES[$role];

        // Cari user yang sudah memiliki role tersebut atau memiliki email demo
        $user = User::role($role)->first()
            ?? User::where('email', $userData['email'])->first();

        // Buat user otomatis jika belum ada dalam database
        if (! $user) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole($role);
        } elseif (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        Auth::login($user, $this->remember);

        session()->regenerate();

        app(ActivityLogService::class)->log(
            action: 'login',
            description: "User berhasil masuk ke dalam sistem via Quick Login ({$role})",
            subject: $user
        );

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Mengisi otomatis kolom form email & password dengan kredensial role demo.
     */
    public function fillCredentials(string $role): void
    {
        if (isset(self::DEMO_ROLES[$role])) {
            $this->email = self::DEMO_ROLES[$role]['email'];
            $this->password = 'password';
            $this->resetErrorBag();
        }
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('livewire.auth.login', [
            'demoRoles' => self::DEMO_ROLES,
        ]);
    }
}
