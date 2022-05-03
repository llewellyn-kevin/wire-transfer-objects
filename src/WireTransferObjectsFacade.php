<?php

namespace LlewellynKevin\WireTransferObjects;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LlewellynKevin\WireTransferObjects\Skeleton\SkeletonClass
 */
class WireTransferObjectsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wire-transfer-objects';
    }
}
