<?php
namespace Nexph\Dev;

class ConsolePrinter
{
    public function info(string $msg): void
    {
        echo "[nexph] $msg\n";
    }

    public function warn(string $msg): void
    {
        echo "[nexph] $msg\n";
    }

    public function error(string $msg): void
    {
        echo "[nexph] $msg\n";
    }

    public function changed(string $file): void
    {
        echo "[nexph] changed: $file\n";
    }

    public function started(): void
    {
        echo "[nexph] dev reload started\n";
    }

    public function restarted(): void
    {
        echo "[nexph] restarted\n";
    }
}
