<?php namespace ORMLike\Shablon\Logger;

define('ORMLike\Shablon\Logger\FAIL',  2);
define('ORMLike\Shablon\Logger\WARN',  4);
define('ORMLike\Shablon\Logger\INFO',  8);
define('ORMLike\Shablon\Logger\DEBUG', 16);
define('ORMLike\Shablon\Logger\ALL',   FAIL | WARN | INFO | DEBUG); // @WTF!

abstract class Logger
{
    const ALL   = ALL;
    const FAIL  = FAIL;
    const WARN  = WARN;
    const INFO  = INFO;
    const DEBUG = DEBUG;

    // disabled as default
    protected $level = 0;

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

    public function checkDirectory() {
        if (empty($this->directory)) {
            throw new \RuntimeException(
                'Log directory is not defined in given configuration! '.
                'Define it using `query_log_directory` key to activate logging.');
        }

        $this->directoryChecked = $this->directoryChecked ?: is_dir($this->directory);
        if (!$this->directoryChecked) {
            $this->directoryChecked = mkdir($this->directory, 0755, true);

            // !!! notice !!!
            // set your log dir secure
            file_put_contents($this->directory .'/index.php',
                "<?php header('HTTP/1.1 403 Forbidden'); ?>");
            // this action is for only apache, see nginx configuration here:
            // http://nginx.org/en/docs/http/ngx_http_access_module.html
            file_put_contents($this->directory .'/.htaccess',
                "Order deny,allow\r\nDeny from all");
        }
    }

    public function setFilenameFormat($filenameFormat) {
        $this->filenameFormat = $filenameFormat;
    }

    public function getFilenameFormat() {
        return $this->filenameFormat;
    }

    abstract public function log($level, $message);
}
