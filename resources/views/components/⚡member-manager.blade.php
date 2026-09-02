<?php

use Livewire\Component;
use App\Models\User;
use Flux\Flux;
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
            ->when(trim($this->search), function ($query) {
                $term = '%' . mb_strtolower(trim($this->search)) . '%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$term])
                      ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
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
            $this->dispatch('toast', title: 'You cannot revoke your own administrator privileges.', category: 'PERMISSION', type: 'danger');
            return;
        }

        $user = User::findOrFail($userId);
        $user->is_admin = !$user->is_admin;
        
        // If they are promoted to admin, reset their tour status so they see the admin tour
        if ($user->is_admin) {
            $user->has_seen_tour = false;
        }

        $user->save();

        // Create notification for the target user
        $adminName = auth()->user()->name;
        if ($user->is_admin) {
            $user->notifications()->create([
                'title' => 'Access Privileges Updated 👑',
                'body' => "You have been promoted to Administrator by {$adminName}.",
                'type' => 'success',
            ]);
        } else {
            $user->notifications()->create([
                'title' => 'Access Privileges Updated 🛡️',
                'body' => "Your administrator role has been revoked by {$adminName}.",
                'type' => 'warning',
            ]);
        }

        $status = $user->is_admin ? 'promoted to Administrator' : 'removed from Administrator';
        $this->dispatch('toast', title: "User {$user->name} successfully {$status}.", category: 'MEMBER', type: 'success');
    }
};
?>

<div class="flex h-full w-full flex-col gap-6 p-3 sm:p-4 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-2 sm:mt-4 pb-16">
    <!-- Main Animated Content -->
    <div class="flex flex-col gap-6 animate-page-entrance">
        <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="users" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Team &amp; Member Management</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Manage workspace member access, administrator roles, and permissions.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <!-- Role Filter Segmented Control -->
            <div class="flex bg-zinc-100 dark:bg-zinc-900 rounded-xl p-1 shrink-0 border border-zinc-200/80 dark:border-zinc-800">
                <button type="button" wire:click="$set('roleFilter', 'all')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $roleFilter === 'all' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    All
                </button>
                <button type="button" wire:click="$set('roleFilter', 'admin')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $roleFilter === 'admin' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    Admin ({{ \App\Models\User::where('is_admin', true)->count() }})
                </button>
                <button type="button" wire:click="$set('roleFilter', 'member')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $roleFilter === 'member' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    Member ({{ \App\Models\User::where('is_admin', false)->count() }})
                </button>
            </div>
            
            <!-- Search bar -->
            <div class="relative flex-1 md:w-60 md:flex-none">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search member by name or email..." 
                       autocomplete="off"
                       class="w-full h-9 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
            </div>
        </div>
    </div>

    <!-- Member Cards Container -->
    <div class="space-y-3.5">
        @forelse($this->members as $member)
            <div wire:key="member-{{ $member->id }}" 
                 class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-4 sm:p-5 shadow-xs hover:border-zinc-400 dark:hover:border-zinc-600 transition-all group flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                
                <div class="flex items-center gap-3.5 flex-1 min-w-0">
                    @if($member->avatar)
                        <img src="{{ $member->avatar }}" alt="{{ $member->name }}" class="size-11 rounded-2xl object-cover border border-zinc-200 dark:border-zinc-700 shrink-0 group-hover:scale-105 transition-transform shadow-xs" />
                    @else
                        <div class="size-11 rounded-2xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 font-extrabold text-sm flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-xs">
                            {{ $member->initials() }}
                        </div>
                    @endif

                    <div class="min-w-0">
                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate flex items-center gap-2 flex-wrap">
                            <span class="group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors">{{ $member->name }}</span>
                            @if($member->is_admin)
                                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[9px] font-mono font-medium uppercase tracking-wider px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700 flex items-center gap-1">
                                    <flux:icon name="shield-check" class="size-3 text-zinc-500 dark:text-zinc-400 shrink-0" />
                                    <span>Administrator</span>
                                </span>
                            @else
                                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-[9px] font-mono font-medium uppercase tracking-wider px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700">
                                    Member
                                </span>
                            @endif
                            @if(auth()->id() === $member->id)
                                <span class="bg-zinc-200 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-[9px] font-mono font-medium uppercase tracking-wider px-2 py-0.5 rounded-md border border-zinc-300 dark:border-zinc-600">You</span>
                            @endif
                        </div>
                        <div class="text-xs font-mono text-zinc-500 dark:text-zinc-400 mt-1 truncate">{{ $member->email }}</div>
                    </div>
                </div>

                <div class="w-full md:w-auto flex justify-end shrink-0 pt-1 md:pt-0">
                    @if(auth()->id() === $member->id)
                        <span class="text-[11px] font-mono text-zinc-500 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 px-3 py-1.5 rounded-xl cursor-not-allowed">
                            Self-editing restricted
                        </span>
                    @else
                        @if($member->is_admin)
                            <button type="button" wire:click="toggleAdmin({{ $member->id }})" 
                                    class="bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs px-3.5 py-1.5 rounded-xl border border-rose-500 active:scale-95 transition-all shadow-xs shadow-rose-500/20 cursor-pointer flex items-center gap-1.5">
                                <flux:icon name="shield-exclamation" class="size-3.5 text-white" />
                                <span>Revoke Admin</span>
                            </button>
                        @else
                            <button type="button" wire:click="toggleAdmin({{ $member->id }})" 
                                    class="bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-xs px-3.5 py-1.5 rounded-xl border border-amber-400/80 active:scale-95 transition-all shadow-xs shadow-amber-500/20 cursor-pointer flex items-center gap-1.5">
                                <flux:icon name="shield-check" class="size-3.5 text-zinc-950" />
                                <span>Make Admin</span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-12 text-center flex flex-col items-center justify-center gap-3">
                <div class="size-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="users" class="size-6" />
                </div>
                <div>
                    <h3 class="font-bold text-sm text-zinc-800 dark:text-zinc-200">No Members Found</h3>
                    <p class="text-xs text-zinc-400 mt-1">No team members matching your search criteria.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-2">
        <flux:pagination :paginator="$this->members" />
    </div>
    </div>
</div>
