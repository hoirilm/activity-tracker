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
                                title: 'Dashboard',
                                description: 'Here you can see a summary of your activities and work progress.',
                                side: 'right',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-tracker',
                            popover: {
                                title: 'Time Tracker',
                                description: 'Start recording your activity time here. Don\'t forget to stop it when you\'re done!',
                                side: 'right',
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-manage',
                            popover: {
                                title: 'Manage',
                                description: 'Manage your Projects, Categories, and Tasks in this menu.',
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
                                description: 'Manage bug reports and feedback submitted by users.',
                                side: 'right',
                                align: 'start'
                            }
                        });
                        tourSteps.push({
                            element: '#tour-members',
                            popover: {
                                title: 'Members (Admin)',
                                description: 'Manage user roles and assign Administrator privileges here.',
                                side: 'right',
                                align: 'start'
                            }
                        });
                        tourSteps.push({
                            element: '#tour-broadcast',
                            popover: {
                                title: 'Broadcast (Admin)',
                                description: 'Send mass announcements and notifications to all users.',
                                side: 'right',
                                align: 'start'
                            }
                        });
                    }

                    tourSteps.push({
                        element: '#tour-help-button',
                        popover: {
                            title: 'Need Help?',
                            description: 'If you encounter any bugs or need help using the app, click this button.',
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
                        doneBtnText: 'Done',
                        nextBtnText: 'Next',
                        prevBtnText: 'Back',
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
