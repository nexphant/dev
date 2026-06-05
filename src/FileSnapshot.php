<?php
namespace Nexph\Dev;

class FileSnapshot
{
    public function __construct(
        public string $path,
        public int $mtime,
        public int $size,
    ) {
    }

    public static function fromPath(string $path): self
    {
        $stat = @stat($path);
        return new self(
            path: $path,
            mtime: $stat['mtime'] ?? 0,
            size: $stat['size'] ?? 0,
        );
    }

    public function changed(FileSnapshot $other): bool
    {
        return $this->mtime !== $other->mtime || $this->size !== $other->size;
    }
}
