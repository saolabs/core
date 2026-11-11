<?php

namespace Saola\Core\Routing;

use InvalidArgumentException;

/** Shared operation vocabulary. A module/context supplies the resource scope. */
enum Permission: string
{
    case ACCESS = 'access';
    case READ = 'read';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case FORCE_DELETE = 'force_delete';
    case EXTRA = 'extra';

    /** Normalize declarations only; serialized metadata contains string values. */
    public static function values(mixed $permissions): array
    {
        if ($permissions === null) {
            return [];
        }
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $values = [];
        foreach ($permissions as $permission) {
            if (! $permission instanceof self) {
                throw new InvalidArgumentException('Declare permissions with Saola\\Core\\Routing\\Permission enum cases, not strings.');
            }
            $values[] = $permission->value;
        }

        return array_values(array_unique($values));
    }
}
