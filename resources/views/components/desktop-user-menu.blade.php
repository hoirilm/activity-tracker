<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :avatar="auth()->user()->avatar"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :src="auth()->user()->avatar"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>

        <flux:menu.separator />

        <!-- Theme Mode Switcher (Light / Dark / Auto) -->
        <div class="px-1 py-1" @click.stop @mousedown.stop @pointerdown.stop @mouseup.stop>
            <div class="flex items-center justify-between p-0.5 bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 rounded-lg" @click.stop @mousedown.stop @pointerdown.stop>
                <button type="button" 
                        @click.stop.prevent="$flux.appearance = 'light'"
                        @mousedown.stop.prevent
                        @pointerdown.stop.prevent
                        :class="$flux.appearance === 'light' ? 'bg-white dark:bg-zinc-700 text-amber-500 dark:text-amber-400 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                        class="flex-1 flex items-center justify-center gap-1 py-1 px-1.5 text-[11px] rounded-md transition-all duration-150 cursor-pointer"
                        title="Light Mode">
                    <flux:icon name="sun" class="size-3.5 shrink-0" />
                    <span>Light</span>
                </button>
                <button type="button" 
                        @click.stop.prevent="$flux.appearance = 'dark'"
                        @mousedown.stop.prevent
                        @pointerdown.stop.prevent
                        :class="$flux.appearance === 'dark' ? 'bg-white dark:bg-zinc-700 text-amber-500 dark:text-amber-400 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                        class="flex-1 flex items-center justify-center gap-1 py-1 px-1.5 text-[11px] rounded-md transition-all duration-150 cursor-pointer"
                        title="Dark Mode">
                    <flux:icon name="moon" class="size-3.5 shrink-0" />
                    <span>Dark</span>
                </button>
                <button type="button" 
                        @click.stop.prevent="$flux.appearance = 'system'"
                        @mousedown.stop.prevent
                        @pointerdown.stop.prevent
                        :class="$flux.appearance === 'system' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                        class="flex-1 flex items-center justify-center gap-1 py-1 px-1.5 text-[11px] rounded-md transition-all duration-150 cursor-pointer"
                        title="System Mode">
                    <flux:icon name="computer-desktop" class="size-3.5 shrink-0" />
                    <span>Auto</span>
                </button>
            </div>
        </div>

        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
