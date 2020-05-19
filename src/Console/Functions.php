<?php

namespace JYmusic\LaravelAddons\Console;

trait Functions
{
    protected function validPhpNamespace($namespace)
    {
        foreach (explode('\\', $namespace) as $part) {
            if (! preg_match('/^[0-9a-zA-Z_]+$/', $part)) {
                return false;
            }

            if (! preg_match('/^[^\d]/', $part)) {
                return false;
            }
        }

        return true;
    }
}
