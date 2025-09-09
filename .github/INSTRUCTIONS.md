# INSTRUCTIONS.md

## Purpose

This document provides high-level guidance for building this Laravel package.  
Follow **Laravel’s conventions** for naming, structure, and developer experience.

---

## General Principles

-   **Expressive API** → Keep syntax clean and fluent (like Eloquent, Horizon, Cache, Queue).
-   **Extensible Design** → Use interfaces & adapters to support multiple backends.
-   **Separation of Concerns** → Keep business logic, contracts, UI, and infra separate.
-   **Convention over Configuration** → Follow Laravel naming & structure first; only add config when necessary.

---

## Naming Conventions

-   Use **singular, descriptive names** for core classes (`StreamManager`, `EventPublisher`, `ConsumerGroup`).
-   Use `Contracts/` for interfaces (`EventStoreDriver`).
-   Use `Drivers/` for implementations (`RedisStreamsDriver`, `KafkaDriver`).
-   Use `Facades/` for the main entry point (`StreamPulse`).
-   Console commands should follow Laravel style: `php artisan streampulse:consume orders`.
-   Config file: `config/streampulse.php`.

---

## Package Structure

src/
├── Contracts/ # Interfaces
├── Drivers/ # Redis, Kafka, etc.
├── Console/ # Artisan commands
├── Http/ # Controllers for UI
├── Providers/ # Service provider
├── Facades/ # Facade classes
├── Support/ # Helpers / traits
└── StreamPulse.php # Main manager
config/
streampulse.php
resources/
views/ # Dashboard UI

---

## Best Practices

-   Bind drivers via the **Service Container** (`app()->bind(EventStoreDriver::class, RedisStreamsDriver::class)`).
-   Ship with **Redis driver as default**, but keep it swappable.
-   Provide **facade + helper functions** for developer convenience.
-   Use **Laravel’s testing helpers** (`Orchestra\Testbench`) for package testing.
-   Document everything in a **README with examples**, like Laravel’s official docs.
-   Versioning → follow **SemVer** (`v1.x` stable for production).

---

## Developer Experience

-   API should feel “Laravel-native” (similar to `Cache::put`, `Queue::push`, etc.).
-   Clear exception messages.
-   Artisan commands with short, memorable names.
-   Provide **publishable config & migrations** if needed.
-   Dashboard UI should follow Horizon’s simplicity.

---
