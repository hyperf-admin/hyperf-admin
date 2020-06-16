<?php
namespace HyperfAdmin\BaseUtils;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class RotatingFileHandler extends StreamHandler
{
    public function __construct($filename, $maxFiles = 0, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        $this->filename = $filename;
        $this->maxFiles = (int)$maxFiles;
        $this->nextRotation = new \DateTime(date('YmdH0000', strtotime('+1 hour')));
        $this->dateFormat = 'YmdH';
        $this->filenameFormat = '{filename}-{date}';
        parent::__construct($this->getTimedFilename(), $level, $bubble, $filePermission, $useLocking);
    }

    /**
     * 修复handler写日志判断级别问题bug
     */
    public function isHandling(array $record): bool
    {
        $level_code = Logger::toMonologLevel($record['level']);

        return $level_code >= $this->level;
    }

    protected function rotate()
    {
        // update filename
        $this->url = $this->getTimedFilename();
        $this->nextRotation = new \DateTime(date('YmdH0000', strtotime('+1 hour')));
        // skip GC of old logs if files are unlimited
        if (0 === $this->maxFiles) {
            return;
        }
        $logFiles = glob($this->getGlobPattern());
        if ($this->maxFiles >= count($logFiles)) {
            // no files to remove
            return;
        }
        // Sorting the files by name to remove the older ones
        usort($logFiles, function ($a, $b) {
            return strcmp($b, $a);
        });
        foreach (array_slice($logFiles, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                // suppress errors here as unlink() might fail if two processes
                // are cleaning up/rotating at the same time
                set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                });
                unlink($file);
                restore_error_handler();
            }
        }
        $this->mustRotate = false;
    }

    protected function getTimedFilename()
    {
        $fileInfo = pathinfo($this->filename);
        $timedFilename = str_replace(['{filename}', '{date}'], [
                $fileInfo['filename'],
                date($this->dateFormat),
            ], $fileInfo['dirname'] . '/' . $this->filenameFormat);

        if (!empty($fileInfo['extension'])) {
            $timedFilename .= '.' . $fileInfo['extension'];
        }

        return $timedFilename;
    }
}
