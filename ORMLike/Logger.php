<?php namespace ORMLike;

use \ORMLike\Exception as Exception;

final class Logger
    extends \ORMLike\Shablon\Logger\Logger
{
    public function log($level, $message) {
        // ensure log directory
        $this->checkDirectory();

        if (($level & $this->level) == 0) return;

        $messagePrepend = '';
        switch ($level) {
            case self::FAIL:
                $messagePrepend = '[FAIL] ';
                break;
            case self::INFO:
                $messagePrepend = '[INFO] ';
                break;
            case self::WARN:
                $messagePrepend = '[WARN] ';
                break;
            case self::DEBUG:
                $messagePrepend = '[DEBUG] ';
                break;
        }

        // prepare filename
        $filename = sprintf('%s/%s.txt',
            $this->directory, date($this->filenameFormat));
        // prepare message
        $message  = sprintf('[%s] %s%s',
            date('D, d M Y H:i:s O'), $messagePrepend, trim($message) ."\n");

        file_put_contents($filename, $message, LOCK_EX | FILE_APPEND);
    }
}
