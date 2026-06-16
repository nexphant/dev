<?php
namespace nexphant\Dev;

class ConsolePrinter
{
    public function info(string $msg): void
    {
        echo "[nexphant] $msg\n";
    }

    public function warn(string $msg): void
    {
        echo "[nexphant] $msg\n";
    }

    public function error(string $msg): void
    {
        echo "[nexphant] $msg\n";
    }

    public function changed(string $file): void
    {
        echo "[nexphant] changed: $file\n";
    }

    public function started(): void
    {
        echo "[nexphant] dev reload started\n";
    }

    public function restarted(): void
    {
        echo "[nexphant] restarted\n";
    }
}
