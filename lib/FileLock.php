<?php

namespace Lib;

/**
 * 檔案鎖類別
 */
class FileLock
{
    /**
     * 檔案開啟狀態指標
     *
     * @var resource|false
     */
    protected $_fp;

    /**
     * 檔案鎖根目錄
     *
     * @var string
     */
    protected $_basePath;

    /**
     * 檔案鎖檔案名稱
     *
     * @var string
     */
    protected $_fileLock;

    /**
     * 物件單一實例
     *
     * @var self|null
     */
    protected static $_uniqueInstance = null;

    /**
     * 取得物件實例
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$_uniqueInstance === null) self::$_uniqueInstance = new self;
        return self::$_uniqueInstance;
    }

    /**
     * 建構子
     *
     * @return void
     */
    protected function __construct()
    {
        $this->_basePath = STORAGE_DIR . DIRECTORY_SEPARATOR . 'filelocks';
    }

    /**
     * 指定檔案名稱，建立檔案鎖；若鎖已存在，則結束整個程序
     *
     * @param  string  $fileName  檔案名稱
     * @return void
     */
    public function lock(string $fileName, string $path = ''): void
    {
        $path = $this->_basePath . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
        $this->_fileLock = $path . DIRECTORY_SEPARATOR . "{$fileName}.lck";

        $this->_fp = fopen($this->_fileLock, 'w+');

        if (flock($this->_fp, LOCK_EX | LOCK_NB) === false)
        {
            fclose($this->_fp);
            exit(CLI_EXECUTING);
        }
    }

    /**
     * 解除檔案鎖
     *
     * @return void
     */
    public function unlock()
    {
        flock($this->_fp, LOCK_UN);
        fclose($this->_fp);
        @unlink($this->_fileLock);
    }
}
