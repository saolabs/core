<?php

namespace Saola\Core\Models;

use Illuminate\Foundation\Auth\User as LaravelAuthenticatable;
use Saola\Core\Concerns\HasSystemModel;

/** Laravel authentication with the same system capabilities as Model. */
abstract class Authenticatable extends LaravelAuthenticatable
{
    use HasSystemModel;
}
