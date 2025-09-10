<!-- Section Header Component -->
<div class="px-4 py-5 border-b border-gray-800 sm:px-6 flex justify-between items-center">
    <h3 class="text-lg font-medium text-white flex items-center">
        @if (isset($icon))
            <i class="fa-solid fa-{{ $icon }} text-redis-red mr-2"></i>
        @endif
        {{ $title }}
    </h3>
    {{ $slot ?? '' }}
</div>
