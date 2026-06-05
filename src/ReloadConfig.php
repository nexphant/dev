<?php
namespace Nexph\Dev;

class ReloadConfig
{
    public function __construct(
        public string $root,
        public string $command,
        public array $watch,
        public array $ignore,
        public array $extensions,
        public int $delayMs,
        public float $gracefulTimeout,
        public bool $restartOnCrash = true,
        public bool $clearScreen = false,
    ) {
    }

    public static function fromArray(array $config): self
    {
        return new self(
            root: $config['root'] ?? getcwd(),
            command: $config['command'] ?? 'php app.php',
            watch: $config['watch'] ?? ['app', 'routes', 'config', 'packages'],
            ignore: $config['ignore'] ?? ['vendor', 'storage', '.git', 'node_modules', 'public/build'],
            extensions: $config['extensions'] ?? ['php', 'env', 'json', 'yml', 'yaml'],
            delayMs: $config['delay'] ?? 300,
            gracefulTimeout: $config['gracefulTimeout'] ?? 3,
            restartOnCrash: $config['restartOnCrash'] ?? true,
            clearScreen: $config['clearScreen'] ?? false,
        );
    }
}
