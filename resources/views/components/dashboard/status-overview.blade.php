<!-- Status Overview Component -->
<div class="bg-gray-900 border border-gray-800 rounded-lg p-4 mb-6">
    <div class="flex items-center mb-3">
        <div class="w-4 h-4 rounded-full bg-{{ $status === 'error' ? 'red' : 'green' }}-500 mr-2"></div>
        <span class="text-gray-300 text-sm">{{ $message ?? 'All systems operational' }}</span>
        <span class="ml-auto text-gray-400 text-xs">Last checked: {{ now()->format('H:i:s') }}</span>
    </div>
</div>
