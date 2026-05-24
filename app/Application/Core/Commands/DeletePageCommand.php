<?php

namespace App\Application\Core\Commands;

final class DeletePageCommand
{
    /** Pages in this list cannot be deleted. */
    private const PROTECTED = [
        'home', 'downloads',
    ];

    public function __construct(
        public readonly string $slug,
    ) {}

    public function isProtected(): bool
    {
        return in_array($this->slug, self::PROTECTED, true);
    }
}
