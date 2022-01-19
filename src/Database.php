<?php

declare(strict_types=1);

namespace Bundsgaard\Lmdb;

use Bundsgaard\Lmdb\Exceptions\CouldNotOpenDatabaseException;
use Throwable;

class Database
{
    public const READ_MODE = 1;
    public const WRITE_MODE = 1;

    private const HANDLER = 'lmdb';
    private const LOCK_FILE_SUFFIX = '-lock';
    private const FILE_MODE = 0644;

    private string $path;
    private int $mode;
    private int $size;
    private string $prefix = '';
    private $handle = null;

    public function __construct(
        string $path,
        int $mode = self::READ_MODE,
        int $size = 4 * 1024 * 1024 * 1024
    ) {
        $this->path = $path;
        $this->mode = $mode;
        $this->size = $size;

        $this->init();
    }

    public function __destruct()
    {
        if (!$this->handle) {
            $this->removeLockFile();

            return;
        }

        $this->close();
    }

    public function put(string $key, string $value): bool
    {
        // dba_replace vs dba_insert? :thinking:
        return dba_replace($this->prefixKey($key), $value, $this->handle);
    }

    public function putMany(array $values): bool
    {
        $manyResult = null;
        foreach ($values as $key => $value) {
            $result = $this->put($key, $value);

            $manyResult = is_null($manyResult) ? $result : $result && $manyResult;
        }

        return $manyResult ?: false;
    }

    public function get(string $key, string $default = null): mixed
    {
        $value = dba_fetch($this->prefixKey($key), $this->handle);

        return $value !== false ? $value : $default;
    }

    public function many(array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function has(string $key): bool
    {
        return dba_exists($this->prefixKey($key), $this->handle);
    }

    public function forget(string $key): bool
    {
        return dba_delete($this->prefixKey($key), $this->handle);
    }

    /**
     * @throws CouldNotOpenDatabaseException
     */
    public function flush(): bool
    {
        try {
            $this->close();
            @unlink($this->path);
            $this->init();
        } catch (Throwable $th) {
            if ($th instanceof CouldNotOpenDatabaseException) {
                throw $th;
            }

            return false;
        }

        return true;
    }

    /**
     * @throws CouldNotOpenDatabaseException
     */
    private function init(): void
    {
        try {
            if (!file_exists($this->path)) {
                file_put_contents($this->path, '', LOCK_EX);
            }

            $this->handle = dba_open(
                $this->path,
                $this->getModeFromFlag($this->mode),
                self::HANDLER,
                self::FILE_MODE,
                $this->size
            );
        } catch (Throwable $th) {
            throw new CouldNotOpenDatabaseException();
        }

        if (!$this->handle) {
            throw new CouldNotOpenDatabaseException();
        }
    }

    private function close(): void
    {
        dba_sync($this->handle);
        dba_close($this->handle);
        $this->removeLockFile();
    }

    private function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }

    private function removeLockFile(): void
    {
        if (file_exists($this->path . self::LOCK_FILE_SUFFIX)) {
            @unlink($this->path . self::LOCK_FILE_SUFFIX);
        }
    }

    private function getModeFromFlag(int $flag): string
    {
        switch ($flag) {
            case self::WRITE_MODE:
                return 'wd';

            case self::READ_MODE:
            default:
                return 'rd';
        }
    }
}
