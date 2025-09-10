<!-- Sidebar Component -->
<div :class="{ 'block': sidebarOpen, 'hidden': !sidebarOpen }"
    class="fixed inset-0 z-40 lg:hidden bg-black/50 backdrop-blur-sm transition-opacity duration-300"
    @click="sidebarOpen = false"></div>

<div :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }"
    class="fixed inset-y-0 left-0 z-40 w-64 transition duration-300 transform bg-gray-900 border-r border-gray-800 lg:static lg:inset-0 lg:translate-x-0 lg:block">
    <div class="flex flex-col h-full sidebar-height">
        @include('stream-pulse::components.navigation.sidebar-nav')
        @include('stream-pulse::components.navigation.sidebar-topics')
        @include('stream-pulse::components.navigation.sidebar-footer')
    </div>
</div>
