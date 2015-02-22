<?php namespace ORMLike\Shablon;

define(__namespace__.'\LOG_FAIL',  2);
define(__namespace__.'\LOG_WARN',  4);
define(__namespace__.'\LOG_INFO',  8);
define(__namespace__.'\LOG_DEBUG', 16);
define(__namespace__.'\LOG_ALL',   LOG_FAIL | LOG_WARN | LOG_INFO | LOG_DEBUG); // @WTF!

abstract class Logger
{
    const LOG_ALL   = LOG_ALL;
    const LOG_FAIL  = LOG_FAIL;
    const LOG_WARN  = LOG_WARN;
    const LOG_INFO  = LOG_INFO;
    const LOG_DEBUG = LOG_DEBUG;

    protected $level;

    protected $directory;
    protected $directoryChecked = false;

    protected $filenameFormat = 'Y-m-d';

    public function setLevel($level) {
        $this->level = $level;
    }

    public function getLevel() {
        return $this->level;
    }

    public function setDirectory($directory) {
        $this->directory = $directory;
    }

    public function getDirectory() {
        return $this->directory;
    }

    public function setFilenameFormat($filenameFormat) {
        $this->filenameFormat = $filenameFormat;
    }

    public function getFilenameFormat() {
        return $this->filenameFormat;
    }

    public function checkDirectory() {
        if (!$this->directoryChecked && !is_dir($this->directory)) {
            $this->directoryChecked = mkdir($this->directory, 0755, true);
        }
    }

    abstract public function log($level, $message);
}
