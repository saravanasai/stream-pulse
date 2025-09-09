<?php

namespace StreamPulse\StreamPulse\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \StreamPulse\StreamPulse\StreamPulse
 */
class StreamPulse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \StreamPulse\StreamPulse\StreamPulse::class;
    }
}
