<?php

namespace Nece\Hound\Cloud\Storage;

class DiskObject implements IObject
{
    /**
     * 文件绝对路径
     *
     * @var string
     */
    private $realname;

    /**
     * 文件路径键值
     *
     * @var string
     */
    private $key;

    /**
     * 构造函数
     *
     * @author nece001@163.com
     * @create 2026-03-29 18:53:07
     *
     * @param string $realname 文件绝对路径
     */
    public function __construct($root_path, $key)
    {
        $root_path = rtrim(str_replace('\\', '/', $root_path), '/');
        $key = ltrim(str_replace('\\', '/', $key), '/');

        $this->key = $key;
        $this->realname = str_replace('/', DIRECTORY_SEPARATOR, ($root_path . '/' . $key));
    }

    /**
     * @inheritDoc
     */
    public function getAccessTime(): int
    {
        $time = fileatime($this->realname);
        return $time !== false ? $time : 0;
    }

    /**
     * @inheritDoc
     */
    public function getCreateTime(): int
    {
        $time = filectime($this->realname);
        return $time !== false ? $time : 0;
    }

    /**
     * @inheritDoc
     */
    public function getModifyTime(): int
    {
        $time = filemtime($this->realname);
        return $time !== false ? $time : 0;
    }

    /**
     * @inheritDoc
     */
    public function getBasename(string $suffix = ""): string
    {
        return basename($this->key, $suffix);
    }

    /**
     * @inheritDoc
     */
    public function getExtension(): string
    {
        $extension = pathinfo($this->key, PATHINFO_EXTENSION);
        return $extension !== null ? $extension : '';
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): string
    {
        return basename($this->key);
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return pathinfo($this->key, PATHINFO_DIRNAME);
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function getRealname(): string
    {
        return $this->realname;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): int
    {
        $size = filesize($this->realname);
        return $size !== false ? $size : 0;
    }

    /**
     * @inheritDoc
     */
    public function getMimeType(): string
    {
        $mimetype = mime_content_type($this->realname);
        return $mimetype !== false ? $mimetype : '';
    }

    /**
     * @inheritDoc
     */
    public function isDir(): bool
    {
        return is_dir($this->realname);
    }

    /**
     * @inheritDoc
     */
    public function isFile(): bool
    {
        return is_file($this->realname);
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        $content = file_get_contents($this->realname);
        return $content !== false ? $content : '';
    }

    /**
     * @inheritDoc
     */
    public function putContent(string $content, bool $append = false): bool
    {
        $dir = dirname($this->realname);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($append) {
            return file_put_contents($this->realname, $content, FILE_APPEND) !== false;
        } else {
            return file_put_contents($this->realname, $content) !== false;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(): bool
    {
        return unlink($this->realname) !== false;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getKey();
    }
}
