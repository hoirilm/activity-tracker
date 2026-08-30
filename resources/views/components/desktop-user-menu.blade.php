<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :avatar="auth()->user()->avatar"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
        class="hover:bg-zinc-100 dark:hover:bg-zinc-900 rounded-xl transition-all cursor-pointer"
    />

    <flux:menu class="rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-2xl shadow-xl p-1.5 min-w-[220px]">
        <div class="flex items-center gap-2.5 px-2 py-2 text-start text-sm">
            <flux:avatar
                :src="auth()->user()->avatar"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                class="size-9 rounded-xl border border-amber-500/30"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <div class="flex items-center gap-1.5">
                    <flux:heading class="truncate font-bold">{{ auth()->user()->name }}</flux:heading>
                    @if(auth()->user()->is_admin ?? false)
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">ADMIN</span>
                    @endif
                </div>
                <flux:text class="truncate text-xs text-zinc-400 dark:text-zinc-500">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator class="my-1 border-zinc-200/60 dark:border-zinc-800/80" />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="rounded-xl text-xs font-medium hover:text-amber-600 dark:hover:text-amber-400 cursor-pointer">
                {{ __('Settings') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer rounded-xl text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-500/10"
                    data-test="logout-button"
                >
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>

