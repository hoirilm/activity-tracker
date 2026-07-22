<?php

use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = 'all';

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getMembersProperty()
    {
        return User::query()
            ->when($this->roleFilter !== 'all', function ($query) {
                $query->where('is_admin', $this->roleFilter === 'admin');
            })
            ->when($this->search, function ($query) {
                $term = '%' . strtolower($this->search) . '%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw('lower(name) like ?', [$term])
                      ->orWhereRaw('lower(email) like ?', [$term]);
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(10);
    }

    public function toggleAdmin($userId)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        // Prevent self-lockout
        if (auth()->id() === (int) $userId) {
            session()->flash('error', 'Anda tidak dapat menghapus hak admin Anda sendiri.');
            return;
        }

        $user = User::findOrFail($userId);
        $user->is_admin = !$user->is_admin;
        $user->save();

        // Create notification for the target user
        $adminName = auth()->user()->name;
        if ($user->is_admin) {
            $user->notifications()->create([
                'title' => 'Hak Akses Diperbarui 👑',
                'body' => "Anda telah dipromosikan sebagai Administrator oleh {$adminName}.",
                'type' => 'success',
            ]);
        } else {
            $user->notifications()->create([
                'title' => 'Hak Akses Diperbarui 🛡️',
                'body' => "Peran administrator Anda telah dicabut oleh {$adminName}.",
                'type' => 'warning',
            ]);
        }

        $status = $user->is_admin ? 'dijadikan Administrator' : 'dihapus dari Administrator';
        session()->flash('status_updated', "User {$user->name} berhasil {$status}.");
    }
};
?>

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">Member Management</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Manage user access roles and admin statuses in this workspace.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <!-- Role Filter Segmented Control -->
            <div class="flex bg-zinc-100 dark:bg-zinc-800/80 rounded-lg p-0.5 shrink-0">
                <button type="button" wire:click="$set('roleFilter', 'all')" 
                        class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer {{ $roleFilter === 'all' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    All
                </button>
                <button type="button" wire:click="$set('roleFilter', 'admin')" 
                        class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer {{ $roleFilter === 'admin' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    Admin
                </button>
                <button type="button" wire:click="$set('roleFilter', 'member')" 
                        class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer {{ $roleFilter === 'member' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    Member
                </button>
            </div>
            
            <!-- Search bar -->
            <div class="flex-1 md:w-64 md:flex-none">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search members..." icon="magnifying-glass" size="sm" autocomplete="off" />
            </div>
        </div>
    </div>

    <!-- Alert status -->
    @if(session()->has('status_updated'))
        <div x-data="{ show: true }" x-show="show" class="p-4 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/30 text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-5 text-emerald-500" />
                <span>{{ session('status_updated') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif
    @if(session()->has('error'))
        <div x-data="{ show: true }" x-show="show" class="p-4 bg-red-50 text-red-800 dark:bg-red-950/30 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/30 text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="size-5 text-red-500" />
                <span>{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif

    <!-- Member Cards List -->
    <div class="grid gap-4">
        @forelse($this->members as $member)
            <div wire:key="member-{{ $member->id }}" class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 p-4 shadow-xs hover:shadow-sm hover:border-zinc-300 dark:hover:border-zinc-700 transition-all duration-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                
                <!-- Account / User Column -->
                <div class="flex items-center gap-3.5 flex-1 min-w-0">
                    <flux:avatar initials="{{ $member->initials() }}" class="size-10 bg-zinc-100/60 dark:bg-zinc-800/40 text-zinc-550 dark:text-zinc-400 border border-zinc-200/30 dark:border-zinc-800/30 shrink-0" />
                    <div class="truncate">
                        <div class="font-medium text-sm text-zinc-850 dark:text-zinc-150 truncate flex items-center gap-2 flex-wrap">
                            <span>{{ $member->name }}</span>
                            @if($member->is_admin)
                                <span class="bg-indigo-100/80 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded">
                                    Administrator
                                </span>
                            @else
                                <span class="bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-450 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded">
                                    Member
                                </span>
                            @endif
                            @if(auth()->id() === $member->id)
                                <span class="bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-455 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">It's You</span>
                            @endif
                        </div>
                        <div class="text-[11px] text-zinc-450 dark:text-zinc-500 mt-1 truncate">{{ $member->email }}</div>
                    </div>
                </div>

                <!-- Action Button Column -->
                <div class="w-full md:w-auto flex justify-end shrink-0">
                    @if(auth()->id() === $member->id)
                        <flux:button variant="subtle" size="sm" disabled class="cursor-not-allowed">Self-editing restricted</flux:button>
                    @else
                        @if($member->is_admin)
                            <flux:button wire:click="toggleAdmin({{ $member->id }})" variant="danger" size="sm" class="cursor-pointer">
                                Revoke Admin
                            </flux:button>
                        @else
                            <flux:button wire:click="toggleAdmin({{ $member->id }})" variant="filled" size="sm" class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 border-none">
                                Make Admin
                            </flux:button>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="text-xs text-neutral-400 text-center py-16 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl flex flex-col items-center gap-2 bg-white dark:bg-zinc-900/50">
                <flux:icon name="users" class="size-8 text-neutral-300 dark:text-neutral-750" />
                <span>No members found matching your search or filters.</span>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-2">
        <flux:pagination :paginator="$this->members" />
    </div>
</div>
