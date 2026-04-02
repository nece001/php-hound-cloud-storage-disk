<?php

namespace Nece\Hound\Cloud\Storage;

use Throwable;

/**
 * 本地硬盘存储
 *
 * @author nece001@163.com
 * @create 2026-03-29 21:36:10
 */
class Disk extends Storage implements IStorage
{
    /**
     * 根路径,例：
     * Linux: /a/b/c
     * Windows: d:\a\b\c
     *
     * @var string
     */
    private string $root_path;

    /**
     * 基础URI,例：
     * http://example.com/files
     *
     * @var string
     */
    private string $base_uri;

    /**
     * 构造
     *
     * @author nece001@163.com
     * @create 2026-03-29 18:35:16
     *
     * @param string $root_path
     */
    public function __construct(string $root_path, string $base_uri)
    {
        $root_path = str_replace('/', DIRECTORY_SEPARATOR, $root_path);

        $base_uri = str_replace('\\', '/', $base_uri);
        $base_uri = rtrim($base_uri, '/');

        $this->root_path = rtrim($root_path, DIRECTORY_SEPARATOR);
        $this->base_uri = $base_uri;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $path): bool
    {
        $full_path = $this->fullPath($path);
        return file_exists($full_path);
    }

    /**
     * @inheritDoc
     */
    public function isDir(string $path): bool
    {
        $full_path = $this->fullPath($path);
        return is_dir($full_path);
    }

    /**
     * @inheritDoc
     */
    public function isFile(string $path): bool
    {
        $full_path = $this->fullPath($path);
        return is_file($full_path);
    }

    /**
     * @inheritDoc
     */
    public function copy(string $from, string $to): bool
    {
        if (!$this->exists($from)) {
            throw new StorageException('源文件或目录不存在：' . $from, Consts::ERROR_CODE_NOT_FOUND);
        }

        $from = $this->fullPath($from);
        $to = $this->fullPath($to);

        if (is_dir($from)) {
            return $this->copyDir($from, $to);
        } else {
            return copy($from, $to);
        }
    }

    /**
     * @inheritDoc
     */
    public function move(string $from, string $to): bool
    {
        try {
            if (!$this->exists($from)) {
                throw new StorageException('源文件或目录不存在：' . $from, Consts::ERROR_CODE_NOT_FOUND);
            }

            $from = $this->fullPath($from);
            $to = $this->fullPath($to);

            return rename($from, $to);
        } catch (Throwable $e) {
            throw new StorageException($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): bool
    {
        $full_path = $this->fullPath($path);
        if (is_dir($full_path)) {
            return $this->deleteDir($full_path);
        } else {
            return unlink($full_path);
        }
    }

    /**
     * @inheritDoc
     */
    public function mkdir(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        $full_path = $this->fullPath($path);
        return mkdir($full_path, $mode, $recursive);
    }

    /**
     * @inheritDoc
     */
    public function rmdir(string $path): bool
    {
        $full_path = $this->fullPath($path);
        if (is_dir($full_path)) {
            return $this->deleteDir($full_path);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function list(string $path = '', int $order = Consts::SCANDIR_SORT_ASCENDING): array
    {
        $full_path = $this->fullPath($path);
        if (!is_dir($full_path)) {
            return [];
        }

        $files = scandir($full_path, $order);
        $result = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filepath = $full_path . DIRECTORY_SEPARATOR . $file;
            $ctime = filectime($filepath);
            $mtime = filemtime($filepath);
            $atime = fileatime($filepath);
            $size = filesize($filepath);
            $is_dir = is_dir($filepath);

            $result[] = $this->buildObjectListItem($file, $size, $is_dir, $ctime, $mtime, $atime);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function upload(string $local_src, string $to): bool
    {
        $to = $this->fullPath($to);
        if (is_dir($local_src)) {
            return $this->copyDir($local_src, $to);
        } else {
            return copy($local_src, $to);
        }
    }

    /**
     * @inheritDoc
     */
    public function download(string $src, string $local_dst): bool
    {
        $src = $this->fullPath($src);
        if (is_dir($src)) {
            return $this->copyDir($src, $local_dst);
        } else {
            return copy($src, $local_dst);
        }
    }

    /**
     * @inheritDoc
     */
    public function file(string $path): IObject
    {
        return new DiskObject($this->root_path, $path);
    }

    /**
     * @inheritDoc
     */
    public function uri(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        if (0 === strpos($path, $this->root_path)) {
            return substr($path, strlen($this->root_path));
        }
        return $path;
    }

    /**
     * @inheritDoc
     */
    public function url(string $path): string
    {
        $uri = $this->uri($path);
        if ($uri === '') {
            return '';
        }
        return $this->base_uri . '/' . $uri;
    }

    /**
     * 构建完整路径
     *
     * @author nece001@163.com
     * @create 2026-03-29 18:35:24
     *
     * @param string $path
     * @return string
     */
    private function fullPath(string $path): string
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $path = trim($path, DIRECTORY_SEPARATOR);
        return $this->root_path . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * 复制目录
     *
     * @author nece001@163.com
     * @create 2026-03-29 18:37:58
     *
     * @param string $from
     * @param string $to
     * @return boolean
     */
    private function copyDir(string $from, string $to): bool
    {
        if (!is_dir($to)) {
            mkdir($to, 0755, true);
        }
        $files = scandir($from);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from_file = $from . DIRECTORY_SEPARATOR . $file;
            $to_file = $to . DIRECTORY_SEPARATOR . $file;
            if (is_dir($from_file)) {
                if (!$this->copyDir($from_file, $to_file)) {
                    return false;
                }
            } else {
                if (!copy($from_file, $to_file)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 删除目录
     *
     * @author nece001@163.com
     * @create 2026-03-29 18:38:11
     *
     * @param string $dir
     * @return boolean
     */
    private function deleteDir(string $dir): bool
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $file_path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file_path)) {
                if (!$this->deleteDir($file_path)) {
                    return false;
                }
            } else {
                if (!unlink($file_path)) {
                    return false;
                }
            }
        }
        return rmdir($dir);
    }
}
