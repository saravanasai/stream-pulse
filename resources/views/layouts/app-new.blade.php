<!DOCTYPE html>
<html lang="en" class="h-full dark">

@include('stream-pulse::components.layout.head', ['title' => 'StreamPulse Dashboard'])

<body class="h-full bg-gray-950 text-white antialiased">
    <div class="min-h-full" x-data="{ sidebarOpen: false, darkMode: true }" x-init="darkMode = true">
        <!-- Top navigation -->
        @include('stream-pulse::components.navigation.top-nav')

        <div class="flex">
            <!-- Sidebar -->
            @include('stream-pulse::components.navigation.sidebar')

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
