<?php

use Livewire\Component;

new class extends Component
{
    public function finishTour()
    {
        if (auth()->check() && !auth()->user()->has_seen_tour) {
            $user = auth()->user();
            $user->has_seen_tour = true;
            $user->save();
        }
    }
};
?>

<div>
    @if(auth()->check() && !auth()->user()->has_seen_tour)
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>

        <script>
            document.addEventListener('livewire:initialized', () => {
                setTimeout(() => {
                    const driver = window.driver.js.driver;
                    const isAdmin = {{ auth()->user()->is_admin ? 'true' : 'false' }};
                    
                    let tourSteps = [
                        {
                            element: '#tour-dashboard',
                            popover: {
                                title: 'Beranda',
                                description: 'Di sini Anda bisa melihat ringkasan aktivitas dan progres pekerjaan Anda.',
                                side: 'right',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-tracker',
                            popover: {
                                title: 'Time Tracker',
                                description: 'Mulai rekam waktu aktivitas Anda di sini. Jangan lupa dimatikan kalau sudah selesai!',
                                side: 'right',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-manage',
                            popover: {
                                title: 'Manajemen',
                                description: 'Atur Proyek, Kategori, dan Daftar Pekerjaan Anda di menu ini.',
                                side: 'right',
                                align: 'start'
                            }
                        }
                    ];

                    if (isAdmin) {
                        tourSteps.push({
                            element: '#tour-issues',
                            popover: {
                                title: 'Issues (Admin)',
                                description: 'Kelola laporan bug dan keluhan dari pengguna.',
                                side: 'right',
                                align: 'start'
                            }
                        });
                        tourSteps.push({
                            element: '#tour-members',
                            popover: {
                                title: 'Members (Admin)',
                                description: 'Kelola hak akses pengguna, jadikan admin, atau cabut akses.',
                                side: 'right',
                                align: 'start'
                            }
                        });
                        tourSteps.push({
                            element: '#tour-broadcast',
                            popover: {
                                title: 'Broadcast (Admin)',
                                description: 'Kirim pengumuman massal ke seluruh pengguna.',
                                side: 'right',
                                align: 'start'
                            }
                        });
                    }

                    tourSteps.push({
                        element: '#tour-help-button',
                        popover: {
                            title: 'Butuh Bantuan?',
                            description: 'Jika Anda menemukan bug atau bingung cara menggunakan aplikasi, klik tombol ini.',
                            side: 'left',
                            align: 'center'
                        }
                    });

                    const tour = driver({
                        showProgress: true,
                        animate: true,
                        allowClose: true,
                        stagePadding: 4,
                        stageRadius: 8,
                        doneBtnText: 'Selesai',
                        nextBtnText: 'Lanjut',
                        prevBtnText: 'Kembali',
                        onDestroyed: () => {
                            @this.call('finishTour');
                        },
                        steps: tourSteps
                    });

                    tour.drive();
                }, 500); // small delay to ensure DOM is ready
            });
        </script>
    @endif
</div>
