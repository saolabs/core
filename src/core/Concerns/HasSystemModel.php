<?php

namespace Saola\Core\Concerns;

/** One set of system capabilities, independent of the Eloquent parent class. */
trait HasSystemModel
{
    use CommonMethods, ModelEventMethods, ModelFileMethods, Uuid;
}
