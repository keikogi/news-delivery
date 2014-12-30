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

    private $flowItem;

    private $typeItem;

    private $source;

    private $isExists;

    private $count;

    private function init()
    {
        if (!self::$mongo) {
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
            self::$logger = new Logger('KeikogiNDT');

            self::$logger->pushHandler(
                new StreamHandler(
                    __DIR__ . '/../../../log/log.log', Logger::DEBUG
                )
            );
        }

        $this->count = 0;
    }

    public function __construct()
    {
        set_time_limit(0);
        date_default_timezone_set('Etc/GMT-5');

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