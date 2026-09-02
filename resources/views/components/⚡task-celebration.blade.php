<?php

use Livewire\Component;

new class extends Component
{
    // Universal Sleek Toast Component
};
?>

<div x-data="{
        show: false,
        title: '',
        category: '',
        type: 'success',
        timer: null,
        toastKey: 0,

        triggerToast(detail) {
            let item = {};
            if (typeof detail === 'string') {
                item = { title: detail, category: 'SUCCESS', type: 'success' };
            } else if (Array.isArray(detail)) {
                let first = detail[0] || {};
                item = typeof first === 'string' ? { title: first } : first;
            } else if (typeof detail === 'object' && detail !== null) {
                item = detail;
            }

            this.title = item.title || item.message || item.text || 'Success';
            this.category = item.category || (item.type === 'danger' || item.type === 'error' ? 'ERROR' : (item.type === 'warning' ? 'WARNING' : 'SUCCESS'));
            this.type = item.type || (item.variant === 'danger' ? 'danger' : (item.variant === 'warning' ? 'warning' : 'success'));
            this.toastKey = Date.now();

            this.show = true;
            if (this.timer) clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.show = false;
            }, 3500);
        },

        dismiss() {
            this.show = false;
            if (this.timer) clearTimeout(this.timer);
        }
     }" 
     @task-completed.window="triggerToast({ category: 'TASK COMPLETED', title: $event.detail ? ($event.detail.title || (Array.isArray($event.detail) ? ($event.detail[0] ? ($event.detail[0].title || $event.detail[0]) : 'Task') : $event.detail)) : 'Task', type: 'success' })"
     @toast.window="triggerToast($event.detail)"
     @notify.window="triggerToast($event.detail)"
     class="relative z-[9999]">

    <style>
        @keyframes toast-progress {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>

    <!-- Sleek Toast Notification -->
    <div x-show="show" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-250 transform"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed bottom-5 right-5 z-[100000] w-[90%] max-w-sm pointer-events-auto">
        
        <div class="relative overflow-hidden rounded-2xl bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl border border-zinc-200/90 dark:border-zinc-700/80 ring-1 ring-black/5 dark:ring-white/15 p-3.5 transition-all"
             :class="{
                 'shadow-2xl shadow-emerald-500/10 dark:shadow-[0_20px_50px_rgba(0,0,0,0.85)] dark:shadow-emerald-950/40': type === 'success' || !type,
                 'shadow-2xl shadow-rose-500/10 dark:shadow-[0_20px_50px_rgba(0,0,0,0.85)] dark:shadow-rose-950/40': type === 'danger' || type === 'error',
                 'shadow-2xl shadow-amber-500/10 dark:shadow-[0_20px_50px_rgba(0,0,0,0.85)] dark:shadow-amber-950/40': type === 'warning'
             }">
            <div class="flex items-center gap-3">
                <!-- Checkmark / Status Badge -->
                <div class="size-8 rounded-xl border flex items-center justify-center shrink-0 shadow-2xs"
                     :class="{
                         'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30': type === 'success' || !type,
                         'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30': type === 'danger' || type === 'error',
                         'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30': type === 'warning'
                     }">
                    <template x-if="type === 'success' || !type">
                        <flux:icon name="check" class="size-4 stroke-2" />
                    </template>
                    <template x-if="type === 'danger' || type === 'error'">
                        <flux:icon name="x-mark" class="size-4 stroke-2" />
                    </template>
                    <template x-if="type === 'warning'">
                        <flux:icon name="exclamation-triangle" class="size-4" />
                    </template>
                </div>

                <!-- Text Content -->
                <div class="flex-1 min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-wider font-sans"
                         :class="{
                             'text-emerald-600 dark:text-emerald-400': type === 'success' || !type,
                             'text-rose-600 dark:text-rose-400': type === 'danger' || type === 'error',
                             'text-amber-600 dark:text-amber-400': type === 'warning'
                         }"
                         x-text="category">
                    </div>
                    <h4 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate mt-0.5 font-sans" x-text="title"></h4>
                </div>

                <!-- Close Button -->
                <button @click="dismiss()" 
                        type="button" 
                        class="p-1.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all rounded-lg cursor-pointer shrink-0">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>

            <!-- Countdown Progress Bar -->
            <div class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-zinc-100 dark:bg-zinc-800/80 overflow-hidden">
                <div :key="toastKey" 
                     class="h-full"
                     style="animation: toast-progress 3500ms linear forwards;"
                     :class="{
                         'bg-emerald-500 dark:bg-emerald-400': type === 'success' || !type,
                         'bg-rose-500 dark:bg-rose-400': type === 'danger' || type === 'error',
                         'bg-amber-500 dark:bg-amber-400': type === 'warning'
                     }"></div>
            </div>
        </div>
    </div>
</div>
