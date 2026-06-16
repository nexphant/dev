<?php
namespace nexphant\Dev;

class Reload
{
    private array $config = [];

    public static function watch(array $paths): self
    {
        $self = new self();
        $self->config['watch'] = $paths;
        return $self;
    }

    public function ignore(array $paths): self
    {
        $this->config['ignore'] = $paths;
        return $this;
    }

    public function ext(array $extensions): self
    {
        $this->config['extensions'] = $extensions;
        return $this;
    }

    public function command(string $command): self
    {
        $this->config['command'] = $command;
        return $this;
    }

    public function delay(int $ms): self
    {
        $this->config['delay'] = $ms;
        return $this;
    }

    public function gracefulTimeout(float $seconds): self
    {
        $this->config['gracefulTimeout'] = $seconds;
        return $this;
    }

    public function root(string $root): self
    {
        $this->config['root'] = $root;
        return $this;
    }

    public function run(): void
    {
        $config = ReloadConfig::fromArray($this->config);
        $printer = new ConsolePrinter();
        $watcher = new FileWatcher($config);
        $runner = new ProcessRunner($config->command, $config->root);

        $printer->started();
        $printer->info('running: ' . $config->command);

        $watcher->snapshot();
        $runner->start();

        $lastChange = 0;
        $pendingRestart = false;

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function () use ($runner, $config) {
            $runner->stop($config->gracefulTimeout);
            exit(0);
        });
        pcntl_signal(SIGTERM, function () use ($runner, $config) {
            $runner->stop($config->gracefulTimeout);
            exit(0);
        });

        while (true) {
            $changed = $watcher->changed();
            if (!empty($changed)) {
                $lastChange = microtime(true);
                $pendingRestart = true;
                foreach ($changed as $file) {
                    $relative = str_replace($config->root . '/', '', $file);
                    $printer->changed($relative);
                }
            }

            if ($pendingRestart && (microtime(true) - $lastChange) * 1000 >= $config->delayMs) {
                $printer->info('restarting...');
                $runner->restart($config->gracefulTimeout);
                $printer->restarted();
                $pendingRestart = false;
            }

            if (!$runner->isRunning()) {
                $code = $runner->exitCode();
                if ($code !== null && $code !== 0) {
                    $printer->error("app crashed with code $code");
                    $printer->info('waiting for change...');
                }
            }

            usleep(200000);
        }
    }
}
