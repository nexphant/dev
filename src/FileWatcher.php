<?php
namespace Nexph\Dev;

class FileWatcher
{
    private array $snapshots = [];

    public function __construct(private ReloadConfig $config)
    {
    }

    public function snapshot(): array
    {
        $files = [];

        foreach ($this->config->watch as $path) {
            $fullPath = $this->resolvePath($path);

            if (!file_exists($fullPath)) {
                continue;
            }

            if (is_file($fullPath)) {
                if ($this->shouldWatch($fullPath)) {
                    $files[$this->normalizePath($fullPath)] = FileSnapshot::fromPath($fullPath);
                }

                continue;
            }

            if (is_dir($fullPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveCallbackFilterIterator(
                        new \RecursiveDirectoryIterator($fullPath, \FilesystemIterator::SKIP_DOTS),
                        function ($current) {
                            return !$this->shouldIgnore($current->getPathname());
                        }
                    )
                );

                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }

                    $filePath = $file->getPathname();

                    if (!$this->shouldWatch($filePath)) {
                        continue;
                    }

                    $files[$this->normalizePath($filePath)] = FileSnapshot::fromPath($filePath);
                }
            }
        }

        return $files;
    }

    public function changed(): array
    {
        $current = $this->snapshot();
        if (empty($this->snapshots)) {
            $this->snapshots = $current;
            return [];
        }
        $changed = [];
        foreach ($current as $path => $snap) {
            if (!isset($this->snapshots[$path])) {
                $changed[] = $path;
            } elseif ($snap->changed($this->snapshots[$path])) {
                $changed[] = $path;
            }
        }
        foreach ($this->snapshots as $path => $snap) {
            if (!isset($current[$path])) {
                $changed[] = $path;
            }
        }
        $this->snapshots = $current;
        return $changed;
    }

    public function shouldWatch(string $path): bool
    {
        if ($this->shouldIgnore($path)) {
            return false;
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($ext, $this->config->extensions, true);
    }

    public function shouldIgnore(string $path): bool
    {
        foreach ($this->config->ignore as $ignore) {
            if (str_contains($path, '/' . $ignore . '/') || str_ends_with($path, '/' . $ignore)) {
                return true;
            }
        }
        return false;
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return rtrim($this->config->root, DIRECTORY_SEPARATOR);
        }

        if ($path === '.') {
            return rtrim($this->config->root, DIRECTORY_SEPARATOR);
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return rtrim($this->config->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    private function normalizePath(string $path): string
    {
        $real = realpath($path);

        if ($real !== false) {
            $path = $real;
        }

        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', rtrim($this->config->root, DIRECTORY_SEPARATOR));

        if (str_starts_with($path, $root . '/')) {
            return substr($path, strlen($root) + 1);
        }

        return $path;
    }

    private function scanDir(string $dir): array
    {
        $files = [];
        $items = @scandir($dir);
        if ($items === false) {
            return [];
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if ($this->shouldIgnore($path)) {
                continue;
            }
            if (is_dir($path)) {
                $files = array_merge($files, $this->scanDir($path));
            } elseif (is_file($path)) {
                $files[] = $path;
            }
        }
        return $files;
    }
}
