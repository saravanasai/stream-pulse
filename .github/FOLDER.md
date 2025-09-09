You are my coding assistant. I am building a Laravel-style package called StreamPulse for event streaming. I need you to scaffold the Redis Streams driver setup.

Project folder strcuture
src/
├── Contracts/ # Interfaces
├── Drivers/ # Redis, Kafka, etc.
├── Console/ # Artisan commands
├── Http/ # Controllers for UI
├── Facades/ # Facade classes
└── StreamPulse.php # Main manager
|\_\_StreamPulseServiceProvider.php
config/streampulse.php
resources/
views/ # Dashboard UI
