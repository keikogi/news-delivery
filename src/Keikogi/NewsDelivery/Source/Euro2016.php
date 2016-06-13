<?php

namespace Keikogi\NewsDelivery\Source;

use Keikogi\NewsDelivery\Core\BaseSource;
use Keikogi\NewsDelivery\Core\SourceInterface;

class Euro2016 extends BaseSource implements SourceInterface
{
    const BASE_URL = 'http://daaseuro2016.uefa.com:80/api/v2/mobile/euro2016/ru/homepage';

    public function url()
    {
        return 'euro2016/' . $this->getItem()->id;
    }

    public function type()
    {
        return 'euro2016';
    }

    public function prepare(&$flowItem, &$typeItem)
    {
        $item = $this->curl($this->getItem()->href);

        if ($item === false) {
            $flowItem = false;
            $typeItem = false;
            return false;
        }

        $fullItem = json_decode($item);

        if (!is_object($fullItem) || !isset($fullItem->editorialItem)) {
            $flowItem = false;
            $typeItem = false;
            return false;
        }

        $pubDate = time();
        $newLink = $this->url();
        $title = (string)$this->getItem()->headline;
        $description = (string)$this->getItem()->comment;

        $flowItem = array(
            'sub_type' => 'euro2016',
            'url' => $newLink,
            'title' => $title,
            'document_type' => $this->type(),
            'published_at' => $pubDate,
            'source' => array(
                'name' => 'UEFA',
                'quote' => $description,
            )
        );

        $typeItem = array(
            'sub_type' => 'euro2016',
            'root' => array(
                'url' => $newLink,
                'title' => $title,
                'document_type' => $this->type(),
                'published_at' => $pubDate,
                'source' => array(
                    'name' => 'UEFA',
                ),
                'content' => array(
                    'body' => $fullItem->editorialItem->body,
                ),
            ),
        );
    }

    public function run()
    {
        $response = $this->curl(self::BASE_URL);

        if ($response === false) {
            return false;
        }

        $euro = json_decode($response);

        if (!is_object($euro) || !isset($euro->mainContent)) {
            return false;
        }

        foreach ($euro->mainContent as $item) {
            if (strpos($item->href, "/articles/") === false) {
                continue;
            }

            $this->setItem($item);
            $this->save();
        }
    }
}
