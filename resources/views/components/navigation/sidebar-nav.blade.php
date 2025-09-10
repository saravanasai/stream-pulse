<!-- Sidebar Navigation Links Component -->
<div class="space-y-1 pt-5 px-3">
    <div class="flex items-center py-2 px-3 text-gray-400 text-xs uppercase tracking-wider font-medium mb-2">
        <i class="fa-solid fa-compass mr-2"></i> Navigation
    </div>
    <a href="{{ route('stream-pulse.dashboard') }}"
        class="flex items-center py-2 px-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white rounded-md group {{ request()->routeIs('stream-pulse.dashboard') ? 'bg-gray-800 text-white' : '' }}">
        <i class="fa-solid fa-gauge-high w-5 h-5 mr-3 text-gray-400 group-hover:text-white"></i>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('stream-pulse.failed') }}"
        class="flex items-center py-2 px-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white rounded-md group {{ request()->routeIs('stream-pulse.failed') ? 'bg-gray-800 text-white' : '' }}">
        <i class="fa-solid fa-triangle-exclamation w-5 h-5 mr-3 text-gray-400 group-hover:text-white"></i>
        <span>Failed Events</span>
        <span class="ml-auto bg-redis-red text-white text-xs font-medium px-2 py-0.5 rounded-full">
            3
        </span>
    </a>
</div>
