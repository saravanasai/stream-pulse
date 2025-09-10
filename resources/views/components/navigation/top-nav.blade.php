<!-- Top Navigation Component -->
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
                            <span class="font-semibold text-xl text-white">Stream Pulse</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-400 text-sm hidden md:block">{{ now()->format('F j, Y') }}</span>
                @include('stream-pulse::components.navigation.notification-bell')
                <button type="button"
                    class="rounded-full bg-gray-800 p-1.5 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fa-solid fa-gear"></i>
                </button>
            </div>
        </div>
    </div>
</nav>
