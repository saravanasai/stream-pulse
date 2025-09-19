<!DOCTYPE html>
<html lang="en" class="h-full dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamPulse Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'redis-red': '#DC382C',
                        'redis-dark': '#A41E11',
                        'accent': {
                            50: '#FEF2F2',
                            100: '#FEE2E2',
                            200: '#FECACA',
                            300: '#FCA5A5',
                            400: '#F87171',
                            500: '#EF4444',
                            600: '#DC2626',
                            700: '#B91C1C',
                            800: '#991B1B',
                            900: '#7F1D1D',
                            950: '#450A0A',
                        },
                    },
                    fontFamily: {
                        'sans': ['Inter var', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        'mono': ['JetBrains Mono', 'ui-monospace', 'monospace'],
                    },
                }
            },
            darkMode: 'class',
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Remove or update the sidebar-height calculation */
        .sidebar-height {
            height: 100%;
            /* Changed from calc(100vh - 64px) to 100% */
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(31, 41, 55, 0.5);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.5);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.7);
        }

        /* Frosted glass effect */
        .frosted {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Pulse Animation */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse-slow {
            animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>

<body class="h-full bg-gray-950 text-white antialiased">
    <div class="min-h-full" x-data="{ sidebarOpen: false, darkMode: true }" x-init="darkMode = true">
        <!-- Top navigation -->
        <nav class="bg-gray-900 border-b border-gray-800 z-50 sticky top-0">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center h-16">
                                <button @click="sidebarOpen = !sidebarOpen" type="button"
                                    class="text-gray-200 p-2 lg:hidden rounded-md hover:bg-gray-800">
                                    <i class="fa-solid fa-bars"></i>
                                </button>
                                <a href="{{ route('stream-pulse.dashboard') }}" class="flex items-center">
                                    <div
                                        class="w-9 h-9 bg-redis-red rounded-md flex items-center justify-center mr-3 shadow-lg">
                                        <i class="fa-solid fa-bolt text-white text-lg"></i>
                                    </div>
                                    <span class="font-semibold text-xl text-white">StreamPulse</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-400 text-sm hidden md:block">{{ now()->format('F j, Y') }}</span>
                        <div class="relative">
                            <button type="button"
                                class="relative rounded-full bg-gray-800 p-1.5 text-gray-300 hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800">
                                <span class="absolute -inset-1.5"></span>
                                <i class="fa-solid fa-bell"></i>
                            </button>
                            <div
                                class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-redis-red flex items-center justify-center text-xs animate-pulse-slow">
                            </div>
                        </div>
                        <button type="button"
                            class="rounded-full bg-gray-800 p-1.5 text-gray-300 hover:bg-gray-700 hover:text-white">
                            <i class="fa-solid fa-gear"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex">
            <!-- Sidebar backdrop -->
            <div :class="{ 'block': sidebarOpen, 'hidden': !sidebarOpen }"
                class="fixed inset-0 z-40 lg:hidden bg-black/50 backdrop-blur-sm transition-opacity duration-300 h-full"
                @click="sidebarOpen = false"></div>



            <!-- Main content -->
            <div class="flex-1 overflow-auto">
                <main class="py-6">
                    <div class="mx-auto px-4 sm:px-6 lg:px-8">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>
</body>

</html>
