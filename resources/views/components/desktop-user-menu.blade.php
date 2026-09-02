<div x-data="{ expanded: false }" 
     @click.outside="expanded = false" 
     @keydown.escape.window="expanded = false" 
     class="desktop-user-menu-wrapper w-full rounded-2xl bg-transparent border border-transparent p-0 transition-all duration-300 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:mx-auto in-data-flux-sidebar-collapsed-desktop:bg-transparent! in-data-flux-sidebar-collapsed-desktop:border-transparent! in-data-flux-sidebar-collapsed-desktop:p-0!"
     :class="expanded ? 'bg-zinc-100/90 dark:bg-zinc-900 border-zinc-200/90 dark:border-zinc-800 shadow-lg p-1.5 ring-1 ring-black/5 dark:ring-white/10' : 'bg-transparent border-transparent p-0'">

    <!-- EXPANDED ACCORDION (Active when sidebar is open/expanded) -->
    <div x-cloak
         style="grid-template-rows: 0fr; opacity: 0; display: grid; pointer-events: none;"
         class="desktop-user-menu-expanded grid grid-rows-[0fr] opacity-0 mb-0 transition-all duration-300 ease-out"
         :class="expanded ? 'grid-rows-[1fr] opacity-100 mb-1.5' : 'grid-rows-[0fr] opacity-0 mb-0'"
         :style="expanded ? 'grid-template-rows: 1fr; opacity: 1; pointer-events: auto;' : 'grid-template-rows: 0fr; opacity: 0; pointer-events: none;'">
        <div class="overflow-hidden" @click.stop>
            <div class="space-y-1.5 p-1">
                <!-- Theme Mode Switcher (Light / Dark / Auto) -->
                <div class="flex items-center justify-between p-0.5 bg-zinc-200/70 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 rounded-xl text-[11px] font-medium shadow-2xs">
                    <button type="button" 
                            @click.stop="$flux.appearance = 'light'"
                            :class="$flux.appearance === 'light' ? 'bg-white dark:bg-zinc-800 text-amber-500 dark:text-amber-400 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'"
                            class="flex-1 flex items-center justify-center gap-1.5 py-1 px-1.5 rounded-lg transition-all duration-150 cursor-pointer"
                            title="Light Mode">
                        <flux:icon name="sun" class="size-3.5 shrink-0" />
                        <span>Light</span>
                    </button>
                    <button type="button" 
                            @click.stop="$flux.appearance = 'dark'"
                            :class="$flux.appearance === 'dark' ? 'bg-white dark:bg-zinc-800 text-amber-500 dark:text-amber-400 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'"
                            class="flex-1 flex items-center justify-center gap-1.5 py-1 px-1.5 rounded-lg transition-all duration-150 cursor-pointer"
                            title="Dark Mode">
                        <flux:icon name="moon" class="size-3.5 shrink-0" />
                        <span>Dark</span>
                    </button>
                    <button type="button" 
                            @click.stop="$flux.appearance = 'system'"
                            :class="$flux.appearance === 'system' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'"
                            class="flex-1 flex items-center justify-center gap-1.5 py-1 px-1.5 rounded-lg transition-all duration-150 cursor-pointer"
                            title="System Mode">
                        <flux:icon name="computer-desktop" class="size-3.5 shrink-0" />
                        <span>Auto</span>
                    </button>
                </div>

                <!-- Action Links (Settings & Logout) -->
                <div class="space-y-0.5 pt-1 border-t border-zinc-200/70 dark:border-zinc-800">
                    <a href="{{ route('profile.edit') }}" 
                       wire:navigate 
                       @click="expanded = false; $dispatch('close-mobile-nav')"
                       class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800 transition-colors cursor-pointer group">
                        <flux:icon name="cog-6-tooth" class="size-4 text-zinc-400 group-hover:text-amber-500 dark:group-hover:text-amber-400 shrink-0 transition-colors" />
                        <span>{{ __('Settings') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" 
                                class="flex items-center gap-2.5 w-full px-2.5 py-1.5 rounded-xl text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer text-left group">
                            <flux:icon name="arrow-right-start-on-rectangle" class="size-4 text-zinc-400 group-hover:text-rose-500 shrink-0 transition-colors" />
                            <span>{{ __('Log out') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Subtle divider above profile anchor -->
            <div class="border-t border-zinc-200/70 dark:border-zinc-800 mt-1"></div>
        </div>
    </div>

    <!-- FLYOUT POPOVER (Active dynamically only when sidebar is collapsed on desktop) -->
    <div x-cloak
         x-show="expanded"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 translate-x-2 scale-95"
         x-transition:enter-end="opacity-100 translate-x-0 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 translate-x-0 scale-100"
         x-transition:leave-end="opacity-0 translate-x-2 scale-95"
         class="desktop-user-menu-flyout hidden lg:block fixed left-16 bottom-4 z-50 w-64 rounded-2xl bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl border border-zinc-200/90 dark:border-zinc-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10 p-2.5 space-y-2 origin-bottom-left"
         @click.stop>
        
        <!-- Header Info in Flyout -->
        <div class="flex items-center gap-3 px-1.5 py-1 text-left">
            <flux:avatar
                :src="auth()->user()->avatar"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                size="sm"
            />
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                    {{ auth()->user()->name }}
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate mt-0.5">
                    {{ auth()->user()->email }}
                </div>
            </div>
        </div>

        <div class="border-t border-zinc-200/80 dark:border-zinc-800"></div>

        <!-- Theme Mode Switcher in Flyout -->
        <div class="px-0.5 py-0.5">
            <div class="flex items-center justify-between p-0.5 bg-zinc-100 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 rounded-xl text-[11px] font-medium">
                <button type="button" 
                        @click.stop="$flux.appearance = 'light'"
                        :class="$flux.appearance === 'light' ? 'bg-white dark:bg-zinc-800 text-amber-500 dark:text-amber-400 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                        class="flex-1 flex items-center justify-center gap-1 py-1 px-1 rounded-lg transition-all duration-150 cursor-pointer"
                        title="Light Mode">
                    <flux:icon name="sun" class="size-3.5 shrink-0" />
                    <span>Light</span>
                </button>
                <button type="button" 
                        @click.stop="$flux.appearance = 'dark'"
                        :class="$flux.appearance === 'dark' ? 'bg-white dark:bg-zinc-800 text-amber-500 dark:text-amber-400 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                        class="flex-1 flex items-center justify-center gap-1 py-1 px-1 rounded-lg transition-all duration-150 cursor-pointer"
                        title="Dark Mode">
                    <flux:icon name="moon" class="size-3.5 shrink-0" />
                    <span>Dark</span>
                </button>
                <button type="button" 
                        @click.stop="$flux.appearance = 'system'"
                        :class="$flux.appearance === 'system' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-2xs font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                        class="flex-1 flex items-center justify-center gap-1 py-1 px-1 rounded-lg transition-all duration-150 cursor-pointer"
                        title="System Mode">
                    <flux:icon name="computer-desktop" class="size-3.5 shrink-0" />
                    <span>Auto</span>
                </button>
            </div>
        </div>

        <div class="border-t border-zinc-200/80 dark:border-zinc-800"></div>

        <!-- Action Links in Flyout -->
        <div class="space-y-0.5 pt-0.5">
            <a href="{{ route('profile.edit') }}" 
               wire:navigate 
               @click="expanded = false" 
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all cursor-pointer group">
                <flux:icon name="cog-6-tooth" class="size-4 text-zinc-400 group-hover:text-amber-500 dark:group-hover:text-amber-400 shrink-0 transition-colors" />
                <span>{{ __('Settings') }}</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" 
                        class="flex items-center gap-2.5 w-full px-2.5 py-1.5 rounded-xl text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-all cursor-pointer text-left group">
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-4 text-zinc-400 group-hover:text-rose-500 shrink-0 transition-colors" />
                    <span>{{ __('Log out') }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Trigger Profile Button (Integrated Anchor) -->
    <button type="button" 
            @click="expanded = !expanded" 
            class="user-menu-trigger group flex items-center w-full p-2 rounded-xl transition-all duration-200 cursor-pointer text-left in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:h-10 in-data-flux-sidebar-collapsed-desktop:p-0 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:mx-auto hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60"
            :class="expanded ? 'bg-zinc-200/50 dark:bg-zinc-800/80' : 'hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
            data-test="sidebar-menu-button">
        <div class="shrink-0 relative">
            <flux:avatar
                :src="auth()->user()->avatar"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                size="sm"
            />
            <span class="absolute -bottom-0.5 -right-0.5 size-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900 in-data-flux-sidebar-collapsed-desktop:hidden"></span>
        </div>
        <div class="in-data-flux-sidebar-collapsed-desktop:hidden mx-2.5 min-w-0 flex-1">
            <div class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-zinc-950 dark:group-hover:text-white truncate">
                {{ auth()->user()->name }}
            </div>
            <div class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium truncate mt-0.5">
                {{ auth()->user()->email }}
            </div>
        </div>
        <div class="in-data-flux-sidebar-collapsed-desktop:hidden shrink-0 ms-auto text-zinc-400 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-all duration-200 transform rotate-0"
             :class="expanded ? 'rotate-180 text-amber-500 dark:text-amber-400' : 'rotate-0'">
            <flux:icon name="chevron-up" class="size-4" />
        </div>
    </button>
</div>
