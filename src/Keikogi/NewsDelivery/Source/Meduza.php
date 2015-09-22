<?php

namespace Keikogi\NewsDelivery\Source;

use Keikogi\NewsDelivery\Core\BaseSource;
use Keikogi\NewsDelivery\Core\SourceInterface;

class Meduza extends BaseSource implements SourceInterface
{
    const BASE_URL = 'https://meduza.io/api/v3/';

    public function url()
    {
        return $this->getItem()->url;
    }

    public function type()
    {
        return $this->getItem()->document_type;
    }

    public function prepare(&$flowItem, &$typeItem)
    {
        $item = $this->curl(self::BASE_URL . $this->url());

        if ($item === false) {
            $typeItem = false;
        } else {
            $typeItem = json_decode($item);
        }
    }

    public function run()
    {
        $response = $this->curl(self::BASE_URL . 'index');

        if ($response === false) {
            return false;
        }

        $meduza = json_decode($response);

        if (!is_object($meduza) || !isset($meduza->documents)) {
            return false;
        }

        foreach ($meduza->documents as $document) {
            $this->setItem($document);
            $this->save();
        }
    }
}
