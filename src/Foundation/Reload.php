<?php
namespace Nexphant;

final class Reload
{
    public static function run(
        string $command = 'php app.php',
        array $watch = ['app', 'routes', 'config', 'packages'],
        array $ignore = ['vendor', 'storage', '.git', 'node_modules'],
        array $extensions = ['php', 'env', 'json', 'yml', 'yaml'],
        int $delay = 300,
        float $gracefulTimeout = 3,
        ?string $root = null,
    ): void {
        \Nexphant\Dev\Reload::watch($watch)
            ->ignore($ignore)
            ->ext($extensions)
            ->command($command)
            ->delay($delay)
            ->gracefulTimeout($gracefulTimeout)
            ->root($root ?? getcwd())
            ->run();
    }
}
