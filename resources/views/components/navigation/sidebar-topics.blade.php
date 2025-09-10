<!-- Sidebar Topics Component -->
<div class="mt-6 px-3">
    <div
        class="flex items-center justify-between py-2 px-3 text-gray-400 text-xs uppercase tracking-wider font-medium mb-2">
        <div><i class="fa-solid fa-stream mr-2"></i> Topics</div>
        <div class="text-gray-400 text-xs cursor-pointer hover:text-white">
            <i class="fa-solid fa-plus"></i>
        </div>
    </div>
    <div class="max-h-60 overflow-y-auto px-2 space-y-1">
        <!-- Side menu topics will be dynamically loaded -->
        @foreach ($topics ?? [] as $topic)
            <a href="{{ route('stream-pulse.topic', ['topic' => $topic]) }}"
                class="flex items-center py-2 px-3 text-sm text-gray-400 hover:bg-gray-800 hover:text-white rounded-md truncate {{ request()->is('*/topic/' . $topic) ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-angle-right w-4 h-4 mr-2 text-gray-500"></i>
                <span>{{ $topic }}</span>
            </a>
        @endforeach

        @if (empty($topics ?? []))
            <a href="#"
                class="flex items-center py-2 px-3 text-sm text-gray-400 hover:bg-gray-800 hover:text-white rounded-md truncate">
                <i class="fa-solid fa-angle-right w-4 h-4 mr-2 text-gray-500"></i>
                <span>user-events</span>
            </a>
            <a href="#"
                class="flex items-center py-2 px-3 text-sm text-gray-400 hover:bg-gray-800 hover:text-white rounded-md truncate">
                <i class="fa-solid fa-angle-right w-4 h-4 mr-2 text-gray-500"></i>
                <span>payment-transactions</span>
            </a>
            <a href="#"
                class="flex items-center py-2 px-3 text-sm text-gray-400 hover:bg-gray-800 hover:text-white rounded-md truncate">
                <i class="fa-solid fa-angle-right w-4 h-4 mr-2 text-gray-500"></i>
                <span>api-logs</span>
            </a>
        @endif
    </div>
</div>
