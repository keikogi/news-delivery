<?php

namespace Keikogi\NewsDelivery\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BaseSource
{
    const URL_FIELD = 'url';

    const URL_TYPE_FIELD = 'root.url';

    const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10) AppleWebKit/600.1.25 (KHTML, like Gecko) Version/8.0 Safari/600.1.25';

    public static $mongo;

    public static $curl;

    public static $logger;

    public static $logPath;

    private $flowItem;

    private $typeItem;

    private $source;

    private $isExists;

    private $count;

    private $debug;

    private function IsDebug() {
        return $this->debug == true;
    }

    private function IsNotDebug() {
        return $this->debug == false;
    }

    private function init()
    {
        if (!self::$mongo && $this->IsNotDebug()) {
            $connection = new \Mongo('localhost');

            self::$mongo = $connection->test;
        }

        if (!self::$curl) {
            self::$curl = curl_init();

            curl_setopt_array(
                self::$curl,
                array(
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_USERAGENT => self::USER_AGENT,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_ENCODING => 'identity',
                )
            );
        }

        if (!self::$logger) {
            self::$logger = new Logger('Keikogi');

            self::$logger->pushHandler(
                new StreamHandler(
                    self::$logPath, Logger::DEBUG
                )
            );
        }

        $this->count = 0;
    }

    public function __construct($options = array())
    {
        set_time_limit(0);
        date_default_timezone_set('Etc/GMT-5');

        if (isset($options['log.path'])) {
            self::$logPath = $options['log.path'];
        } else {
            self::$logPath = 'news_delivery.log';
        }

        $this->debug = false;
        if (isset($options['debug'])) {
            $this->debug = $options['debug'];
        }

        $this->init();
    }

    public function curl($url)
    {
        $this->init();

        curl_setopt_array(
            self::$curl,
            array(
                CURLOPT_URL => $url,
                CURLOPT_REFERER => $url,
            )
        );

        return curl_exec(self::$curl);
    }

    public function setItem($item)
    {
        $this->flowItem = $item;
        $this->typeItem = $item;
        $this->isExists = $this->checkExists();

        if (!$this->isExists) {
            $this->prepare(
                $this->flowItem,
                $this->typeItem
            );
        }
    }

    public function getItem()
    {
        return $this->flowItem;
    }

    public function getTypeItem()
    {
        return $this->typeItem;
    }

    public function checkExists()
    {
        if ($this->IsDebug()) {
            return false;
        }

        $type = $this->type();

        $itemCount = self::$mongo->items->find(
            array(
                self::URL_FIELD => $this->url()
            )
        )->count();

        if ($itemCount) {
            return true;
        }

        $typeCount = self::$mongo->$type->find(
            array(
                self::URL_TYPE_FIELD => $this->url()
            )
        )->count();

        if ($typeCount) {
            return true;
        }

        return false;
    }

    public function prepare(&$flowItem, &$typeItem)
    {
        return false;
    }

    public function save()
    {
        if ($this->isExists || !$this->getItem() || !$this->getTypeItem()) {
            return false;
        }

        ++$this->count;
        $type = $this->type();

        if ($this->IsDebug()) {
            echo "PREVIEW:\n";
            var_dump($this->getItem());
            echo "FULL:\n";
            var_dump($this->getTypeItem());
            echo "\n\n";
            return true;
        }

        self::$mongo->items->insert($this->getItem());
        self::$mongo->$type->insert($this->getTypeItem());
    }

    public function __destruct()
    {
        if (self::$curl) {
            curl_close(self::$curl);
        }

        self::$curl = false;

        $info = get_class($this) . ': ' . $this->count;

        self::$logger->addDebug($info);
        echo $info . "\n";
    }
}
