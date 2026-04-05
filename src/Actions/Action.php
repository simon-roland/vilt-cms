<?php

namespace RolandSolutions\ViltCms\Actions;

abstract class Action
{
    public static function make(): static
    {
        return app(static::class);
    }
}
