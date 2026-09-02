<?php

use Livewire\Component;

new class extends Component
{
    // Single-file Livewire component
};
?>

<div x-data="{
        show: false,
        taskTitle: '',
        appreciationMessage: '',
        progress: 100,
        timer: null,
        progressInterval: null,

        messages: [
            'Luar biasa! Satu langkah lebih dekat menuju pencapaian goal besarmu! 🚀',
            'Kerja bagus! Selesainya task ini membuktikan produktivitasmu yang tinggi. 🏆',
            'Awesome! Tetap pertahankan ritme dan semangat hebat ini! 💪',
            'Mantap! Satu target selesai dengan sempurna. Kamu luar biasa! 🔥',
            'Selamat! Konsistensi dan fokusmu membuahkan hasil manis! ✨',
            'Great Job! Skill produktivitasmu makin hari makin meningkat! 🎯'
        ],

        triggerCelebration(title) {
            this.taskTitle = title || 'Task';
            this.appreciationMessage = this.messages[Math.floor(Math.random() * this.messages.length)];
            
            this.show = true;
            this.progress = 100;

            this.$nextTick(() => {
                this.launchConfetti();
            });
            this.startTimer();
        },

        dismiss() {
            this.show = false;
            if (this.timer) clearTimeout(this.timer);
            if (this.progressInterval) clearInterval(this.progressInterval);
        },

        startTimer() {
            if (this.timer) clearTimeout(this.timer);
            if (this.progressInterval) clearInterval(this.progressInterval);

            const duration = 4500;
            const intervalStep = 50;
            const decrement = (intervalStep / duration) * 100;

            this.progressInterval = setInterval(() => {
                this.progress = Math.max(0, this.progress - decrement);
            }, intervalStep);

            this.timer = setTimeout(() => {
                this.dismiss();
            }, duration);
        },

        launchConfetti() {
            const canvas = this.$refs.confettiCanvas;
            if (!canvas) return;

            canvas.style.display = 'block';
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const ctx = canvas.getContext('2d');

            const particles = [];
            const particleCount = 140;
            const colors = ['#10b981', '#f59e0b', '#ec4899', '#3b82f6', '#8b5cf6', '#06b6d4', '#eab308'];

            for (let i = 0; i < particleCount; i++) {
                const isLeft = i % 2 === 0;
                particles.push({
                    x: isLeft ? canvas.width * 0.25 : canvas.width * 0.75,
                    y: canvas.height * 0.75,
                    vx: (isLeft ? 1 : -1) * (Math.random() * 14 + 5),
                    vy: -(Math.random() * 18 + 10),
                    size: Math.random() * 9 + 6,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    rotation: Math.random() * 360,
                    rotationSpeed: (Math.random() - 0.5) * 14,
                    gravity: 0.45,
                    drag: 0.96,
                    opacity: 1,
                    shape: Math.random() > 0.4 ? 'rect' : 'circle'
                });
            }

            let animationFrameId;
            const render = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                let aliveCount = 0;

                particles.forEach(p => {
                    if (p.opacity <= 0) return;

                    p.vx *= p.drag;
                    p.vy *= p.drag;
                    p.vy += p.gravity;

                    p.x += p.vx;
                    p.y += p.vy;
                    p.rotation += p.rotationSpeed;
                    
                    if (p.y > canvas.height * 0.7) {
                        p.opacity -= 0.025;
                    }

                    if (p.opacity > 0) {
                        aliveCount++;
                        ctx.save();
                        ctx.globalAlpha = Math.max(0, p.opacity);
                        ctx.translate(p.x, p.y);
                        ctx.rotate((p.rotation * Math.PI) / 180);

                        ctx.fillStyle = p.color;
                        if (p.shape === 'rect') {
                            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 1.4);
                        } else {
                            ctx.beginPath();
                            ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                            ctx.fill();
                        }

                        ctx.restore();
                    }
                });

                if (aliveCount > 0) {
                    animationFrameId = requestAnimationFrame(render);
                } else {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    canvas.style.display = 'none';
                    cancelAnimationFrame(animationFrameId);
                }
            };

            render();
        }
     }" 
     @task-completed.window="triggerCelebration($event.detail ? ($event.detail.title || (Array.isArray($event.detail) ? ($event.detail[0] ? ($event.detail[0].title || $event.detail[0]) : 'Task') : $event.detail)) : 'Task')"
     class="relative z-[9999]">
    
    <!-- Canvas Overlay for Confetti Particles -->
    <canvas x-ref="confettiCanvas" 
            class="pointer-events-none fixed inset-0 w-full h-full z-[99999]"
            style="display: none;"></canvas>

    <!-- Celebration Toast Modal -->
    <div x-show="show" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-500 transform"
         x-transition:enter-start="opacity-0 -translate-y-12 scale-90"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-300 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-8 scale-95"
         class="fixed top-6 inset-x-0 mx-auto w-[92%] max-w-md z-[100000] pointer-events-auto">
        
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-2xl p-5">
            <div class="relative flex items-start gap-4">
                <!-- Glowing Checkmark / Trophy Icon Badge -->
                <div class="shrink-0 relative">
                    <div class="size-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/30 flex items-center justify-center">
                        <div class="size-full bg-emerald-500 rounded-[14px] flex items-center justify-center text-white">
                            <flux:icon name="check-circle" class="size-7 stroke-[2.5]" />
                        </div>
                    </div>
                    <span class="absolute -top-1 -right-1 text-base animate-bounce">🎉</span>
                </div>

                <!-- Text Content -->
                <div class="flex-1 min-w-0 pr-6">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider uppercase text-emerald-600 dark:text-emerald-400 mb-0.5">
                        <flux:icon name="sparkles" class="size-3.5" />
                        <span>Task Completed!</span>
                    </div>

                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate" x-text="taskTitle"></h3>
                    
                    <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-1 leading-relaxed font-medium" x-text="appreciationMessage"></p>
                </div>

                <!-- Close Button -->
                <button @click="dismiss()" 
                        type="button" 
                        class="absolute top-0 right-0 p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors rounded-lg cursor-pointer">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>

            <!-- Auto-dismiss Progress Line -->
            <div class="mt-4 h-1.5 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-400 transition-all ease-linear"
                     :style="`width: ${progress}%; transition-duration: 50ms;`"></div>
            </div>
        </div>
    </div>
</div>
