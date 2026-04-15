<?php

namespace Saola\Core\Contracts;

interface AuditableInterface
{
    /**
     * Get the user who created the record
     */
    public function creator();

    /**
     * Get the user who last updated the record
     */
    public function updater();

    /**
     * Get the user who deleted the record
     */
    public function deleter();
} 