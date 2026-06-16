<?php

namespace Nexphant\Dev;

class ProcessRunner
{
    private $process = null;
    private ?int $pid = null;
    private ?int $exitCode = null;
    private array $pipes = [];

    public function __construct(private string $command, private string $cwd)
    {
    }

    public function start(): void
    {
        $this->stop(0.1);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => STDOUT,
            2 => STDERR,
        ];

        $this->process = proc_open($this->command, $descriptors, $pipes, $this->cwd);

        if (!is_resource($this->process)) {
            $this->process = null;
            $this->pipes = [];
            return;
        }

        $this->pipes = $pipes;

        foreach ($this->pipes as $pipe) {
            $this->closePipe($pipe);
        }

        $status = proc_get_status($this->process);
        $this->pid = $status['pid'] ?? null;
        $this->exitCode = null;
    }

    public function stop(float $timeout): void
    {
        if (!is_resource($this->process)) {
            $this->cleanup();
            return;
        }

        if ($this->isRunning()) {
            if ($this->pid && function_exists('posix_kill')) {
                @posix_kill($this->pid, SIGTERM);
            } else {
                @proc_terminate($this->process);
            }

            $start = microtime(true);

            while ($this->isRunning() && (microtime(true) - $start) < $timeout) {
                usleep(50_000);
            }

            if ($this->isRunning()) {
                if ($this->pid && function_exists('posix_kill')) {
                    @posix_kill($this->pid, SIGKILL);
                }

                @proc_terminate($this->process, 9);
            }
        }

        $this->close();
    }

    public function restart(float $timeout): void
    {
        $this->stop($timeout);
        usleep(100_000);
        $this->start();
    }

    public function isRunning(): bool
    {
        if (!is_resource($this->process)) {
            return false;
        }

        $status = @proc_get_status($this->process);

        if (!is_array($status)) {
            return false;
        }

        if (!$status['running']) {
            $this->exitCode = $status['exitcode'] ?? null;
            return false;
        }

        return true;
    }

    public function exitCode(): ?int
    {
        return $this->exitCode;
    }

    private function close(): void
    {
        foreach ($this->pipes as $pipe) {
            $this->closePipe($pipe);
        }

        $this->pipes = [];

        if (is_resource($this->process)) {
            @proc_close($this->process);
        }

        $this->cleanup();
    }

    private function cleanup(): void
    {
        $this->process = null;
        $this->pid = null;
        $this->pipes = [];
    }

    private function closePipe(mixed $pipe): void
    {
        if (is_resource($pipe) && get_resource_type($pipe) === 'stream') {
            @fclose($pipe);
        }
    }
}