src/
│
├── StreamPulse.php # Main facade implementation and API entry point
├── StreamPulseServiceProvider.php # Laravel service provider for package registration
│
├── Commands/ # CLI commands
│ └── StreamPulseCommand.php # Artisan command implementation
│
├── Contracts/ # Interfaces defining core abstractions
│ ├── EventStoreDriver.php # Driver contract for event storage backends
│ └── StreamUIInterface.php # Interface for dashboard UI components
│
├── Drivers/ # Backend driver implementations
│ └── RedisStreamsDriver.php # Redis Streams implementation of EventStoreDriver
│
├── Facades/ # Laravel facades
│ └── StreamPulse.php # Facade for accessing StreamPulse functionality
│
├── Http/ # HTTP-related components
│ └── Controllers/ # Dashboard controllers
│ └── StreamPulseDashboardController.php # Controller for dashboard routes
│
├── Providers/ # Additional service providers
│ └── StreamPulseUIServiceProvider.php # Provider for UI-specific services
│
└── Support/ # Helper and utility classes
└── TransactionAwareEvents.php # Handles transaction-bound event publishing

Core Components
StreamPulse.php
The primary class that implements the main API surface for the package. It provides methods for:

Publishing events to topics
Consuming events via handlers
Transaction-aware event publishing
dead letter queue management

This class serves as the central coordination point, delegating operations to the appropriate driver.

This class serves as the central coordination point, delegating operations to the appropriate driver.

StreamPulseServiceProvider.php
Registers the package with Laravel, handling:

Configuration registration
View publishing
Command registration
Service bindings for dependency injection
Event listeners for database transactions

Contracts
EventStoreDriver.php
Interface that defines the contract for backend storage implementations:

Methods for publishing events
Methods for consuming events
Topic management operations
Error handling and recovery operations
This abstraction allows for multiple backend implementations beyond Redis.

StreamUIInterface.php
Defines the contract for dashboard UI components:

Topic listing and details
Event rendering
Analytics data
User interface interactions

Drivers
RedisStreamsDriver.php
Redis Streams implementation of the EventStoreDriver interface:

Uses Redis Streams as a backend for event storage
Implements consumer groups for distributed processing
Handles acknowledgments and failures
Manages dead letter queues
Provides retention policy enforcement

Facades
StreamPulse.php
Laravel facade that provides static access to the StreamPulse functionality:

Simplifies usage in application code
Follows Laravel facade pattern for service location

HTTP Components
StreamPulseDashboardController.php
Controller that handles dashboard routes:

Dashboard home page
Topic listing and details
Event inspection
Failed event management
Analytics and visualizations
Providers
StreamPulseUIServiceProvider.php
Service provider specific to UI components:

Registers dashboard routes
Binds UI-specific services
Configures middleware for dashboard access
Sets up view composers for dynamic content
Support Classes
TransactionAwareEvents.php
Handles transaction-bound event publishing:

Stores events during a database transaction
Publishes events only after successful commit
Discards events if transaction fails
Integrates with Laravel's transaction events

UI component structure

resources/
│
├── images/ # Dashboard UI images
│ ├── dashboard.png # Dashboard preview image
│ └── logo.png # StreamPulse logo
│
└── views/ # Blade templates
├── components/ # Reusable UI components
│ ├── cards/ # Statistic and data cards
│ ├── dashboard/ # Dashboard-specific components
│ ├── layout/ # Layout structure components
│ ├── navigation/ # Navigation elements
│ └── ui/ # UI utilities and headers
│
├── dashboard/ # Main dashboard views
│ ├── event.blade.php # Event detail view
│ ├── failed.blade.php # Failed events view
│ ├── index.blade.php # Dashboard homepage
│ └── topic.blade.php # Topic detail view
│
└── layouts/ # Master layouts
└── app.blade.php # Main application layout
