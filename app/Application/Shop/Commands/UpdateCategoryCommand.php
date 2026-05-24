<?php

namespace App\Application\Shop\Commands;

final class UpdateCategoryCommand
{
    public function __construct(
        public readonly int     $id,
        public readonly ?string $name        = null,
        public readonly bool    $setParent   = false,
        public readonly ?int    $parentId    = null,
        public readonly ?int    $position    = null,
        public readonly bool    $setBanner   = false,
        public readonly ?string $bannerImage = null,
    ) {}
}
