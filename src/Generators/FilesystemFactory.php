<?php

namespace JYmusic\LaravelAddons\Generators;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as LocalAdapter;

class FilesystemFactory
{
    /**
     * Create a local filesystem.
     *
     * @param string $root
     *
     * @return \League\Flysystem\Filesystem
     */
    public static function local($root)
    {
        return new Filesystem(new LocalAdapter($root));
    }
}
