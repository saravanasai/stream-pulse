<?php

use StreamPulse\StreamPulse\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Mark all Integration tests with the 'integration' group
uses()->group('integration')->in('Integration');
