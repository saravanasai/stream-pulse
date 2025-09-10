<!-- Stat Card Component -->
<div
    class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0 rounded-md p-3 bg-{{ $color ?? 'blue' }}-500 bg-opacity-10">
                <i class="fa-solid fa-{{ $icon ?? 'layer-group' }} text-{{ $color ?? 'blue' }}-400 text-xl"></i>
            </div>
            <div class="ml-5">
                <p class="text-sm font-medium text-gray-400">{{ $title }}</p>
                <div class="flex items-baseline">
                    <p class="text-2xl font-semibold text-white">{{ $value }}</p>
                    @if (isset($trend))
                        <span class="ml-2 text-xs text-{{ $trendColor ?? 'green' }}-400">{{ $trend }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="bg-gray-800 px-5 py-3">
        @if (isset($footerLink) && isset($footerText))
            <a href="{{ $footerLink }}"
                class="text-sm text-{{ $color ?? 'blue' }}-400 hover:text-{{ $color ?? 'blue' }}-300 flex items-center">
                <span>{{ $footerText }}</span>
                <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
            </a>
        @else
            <span class="text-sm text-gray-400">{{ $footer ?? '' }}</span>
        @endif

        @if (isset($progressValue))
            <div class="w-full bg-gray-700 rounded-full h-1.5">
                <div class="bg-{{ $color ?? 'yellow' }}-500 h-1.5 rounded-full" style="width: {{ $progressValue }}%">
                </div>
            </div>
        @endif
    </div>
</div>
