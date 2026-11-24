<?php

namespace App\Livewire\Admin;

use App\Domains\Audit\Services\ActivityLogService;
use App\Models\User;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Manajemen User')]
class UserIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $filterRole = '';

    public string $filterStatus = 'active'; // 'active', 'trashed', 'all'

    // Form state
    public bool $showModal = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $selectedRole = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRole(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', User::class);

        $this->reset(['editingUserId', 'name', 'email', 'password', 'password_confirmation', 'selectedRole']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openEditModal(int $userId): void
    {
        $user = User::withTrashed()->findOrFail($userId);
        Gate::authorize('update', $user);

        $this->resetErrorBag();
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRole = $user->roles->first()?->name ?? '';
        $this->showModal = true;
    }

    public function save(ActivityLogService $logger): void
    {
        if ($this->editingUserId) {
            $user = User::withTrashed()->findOrFail($this->editingUserId);
            Gate::authorize('update', $user);

            $this->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => 'nullable|string|min:8|confirmed',
                'selectedRole' => 'nullable|string|exists:roles,name',
            ]);

            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
            ];

            $user->name = $this->name;
            $user->email = $this->email;
            if (filled($this->password)) {
                $user->password = Hash::make($this->password);
            }
            $user->save();

            if (filled($this->selectedRole) && Gate::allows('manageRoles', $user)) {
                $user->syncRoles([$this->selectedRole]);
            }

            $newValues = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
            ];

            $logger->log(
                action: 'update',
                description: "Memperbarui profil user {$user->name} ({$user->email})",
                subject: $user,
                oldValues: $oldValues,
                newValues: $newValues
            );

            session()->flash('success', "User {$user->name} berhasil diperbarui.");
        } else {
            Gate::authorize('create', User::class);

            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'selectedRole' => 'required|string|exists:roles,name',
            ]);

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            if (filled($this->selectedRole)) {
                $user->assignRole($this->selectedRole);
            }

            $logger->log(
                action: 'create',
                description: "Menambahkan user baru {$user->name} ({$user->email}) dengan role {$this->selectedRole}",
                subject: $user,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->selectedRole,
                ]
            );

            session()->flash('success', "User {$user->name} berhasil ditambahkan.");
        }

        $this->showModal = false;
    }

    public function deleteUser(int $userId, ActivityLogService $logger): void
    {
        $user = User::findOrFail($userId);
        Gate::authorize('delete', $user);

        try {
            $userName = $user->name;
            $userEmail = $user->email;
            $user->delete();

            $logger->log(
                action: 'delete',
                description: "Menonaktifkan / menghapus user {$userName} ({$userEmail})",
                subject: $user
            );

            session()->flash('success', "User {$userName} berhasil dinonaktifkan.");
        } catch (Exception $e) {
            session()->flash('error', "Gagal menonaktifkan user: {$e->getMessage()}");
        }
    }

    public function restoreUser(int $userId, ActivityLogService $logger): void
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        Gate::authorize('restore', $user);

        try {
            $user->restore();

            $logger->log(
                action: 'restore',
                description: "Mengaktifkan kembali user {$user->name} ({$user->email})",
                subject: $user
            );

            session()->flash('success', "User {$user->name} berhasil diaktifkan kembali.");
        } catch (Exception $e) {
            session()->flash('error', "Gagal mengaktifkan user: {$e->getMessage()}");
        }
    }

    public function render(): View
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query()->with('roles');

        if ($this->filterStatus === 'trashed') {
            $query->onlyTrashed();
        } elseif ($this->filterStatus === 'all') {
            $query->withTrashed();
        }

        if (filled($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (filled($this->filterRole)) {
            $query->role($this->filterRole);
        }

        return view('livewire.admin.user-index', [
            'users' => $query->latest('id')->paginate(15),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
