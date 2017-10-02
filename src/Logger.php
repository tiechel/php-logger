<?php

class Logger {
    const STDOUT = 'php://stdout';

    const DEBUG  = 100;
    const TRACE  = 150;
    const INFO   = 200;
    const NOTICE = 250;
    const WARN   = 300;
    const ERROR  = 400;
    const FATAL  = 500;

    protected static $levels = array(
        100 => 'DEBUG',
        150 => 'TRACE',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARN',
        400 => 'ERROR',
        500 => 'FATAL'
    );
    private static $level  = 100;       // ログレベル
    private static $append = true;      // 追記モード


    private static $filepath = self::STDOUT;    // ログファイル名
    private static $fp = null;                  // ファイルハンドラ

    /******************************
        ログファイル出力先とログレベルの設定
    ******************************/
    public static function setFilepath($filepath)
    {
        self::$filepath = $filepath;
    }

    public static function setLevel($level)
    {
        self::$level = $level;
    }

    /******************************
        ログファイルを開く
    ******************************/
    private static function open_file(){
        if (self::$filepath !== self::STDOUT) {
            if (!is_file(self::$filepath)) {
                $dir = dirname(self::$filepath);
                if (!is_dir($dir)) {
                    $success = mkdir($dir, 0777, true);
                    if ($success === false) {
                        error_log("Failed to created a directory. {$dir}");
                        return false;
                    }
                }
            }
        }

        // ファイルを開きポインタを後ろに移動する
        $mode = self::$append ? 'a' : 'w';
        $fp = fopen(self::$filepath, $mode);
        if ($fp === false) {
            error_log("Failed to open.");
            $fp = null;
            return false;
        }
        fseek($fp, 0, SEEK_END);
        return $fp;
    }

    /******************************
        書き込み
    ******************************/
    protected static function write($str){
        if (self::$filepath === null) {
            error_log($str);
            return;
        }
        if (($fp = self::open_file()) === false) {
            return;
        }
        if (fwrite($fp, $str.PHP_EOL) === false) {
            error_log("Failed to write.");
        }
    }

    /******************************
        ログ出力
    ******************************/
    protected static function log($level, $value) {
        if (self::$level > $level) return;

        // 配列とオブジェクトはStringに変換
        if (is_object($value)) {
            $rc = new ReflectionClass(get_class($value));
            if ($rc->hasMethod('__toString')) {
                $value = (string)$value;
            } else {
                $value = var_export($value, true);
            }
        } else if (is_array($value)) {
            $value = var_export($value, true);
        }

        $level_str = self::$levels[$level];
        self::write(self::logformat($level_str, $value));
    }

    public static function debug ($value) { self::log(self::DEBUG,  $value); }
    public static function trace ($value) { self::log(self::TRACE,  $value); }
    public static function info  ($value) { self::log(self::INFO,   $value); }
    public static function notice($value) { self::log(self::NOTICE, $value); }
    public static function warn  ($value) { self::log(self::WARN,   $value); }
    public static function error ($value) { self::log(self::ERROR,  $value); }
    public static function fatal ($value) { self::log(self::FATAL,  $value); }

    /******************************
        ログの書式設定
    ******************************/
    public static function logformat($level_str, $str) {
        $str = rtrim($str, "\n");

        $logstr = sprintf("%s [%s] %s", date("Y-m-d H:i:s"), $level_str, $str);
        return $logstr;
    }

}