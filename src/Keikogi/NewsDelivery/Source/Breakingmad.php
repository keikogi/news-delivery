<?php

namespace Keikogi\NewsDelivery\Source;

use Keikogi\NewsDelivery\Core\BaseSource;
use Keikogi\NewsDelivery\Core\SourceInterface;

class Breakingmad extends BaseSource implements SourceInterface
{
    const BASE_URL = 'http://breakingmad.me/ru/rss';

    public function url()
    {
        return str_ireplace(
            array('http://breakingmad.me/', '/ru/'),
            array('breaking/', '/'),
            $this->getItem()->link
        );
    }

    public function type()
    {
        return 'breaking';
    }

    public function prepare(&$flowItem, &$typeItem)
    {
        $pubDate = time();
        $newLink = $this->url();
        $title = (string)$this->getItem()->title;
        $originalLink = (string)$this->getItem()->link;
        $description = (string)$this->getItem()->description;

        $flowItem = array(
            'sub_type' => 'breakingmad',
            'url' => $newLink,
            'title' => $title,
            'document_type' => $this->type(),
            'published_at' => $pubDate,
            'source' => array(
                'name' => 'Breaking Mad',
                'url' => $originalLink,
            )
        );

        $typeItem = array(
            'sub_type' => 'breakingmad',
            'root' => array(
                'url' => $newLink,
                'title' => $title,
                'document_type' => $this->type(),
                'published_at' => $pubDate,
                'source' => array(
                    'name' => 'Breaking Mad',
                    'url' => $originalLink,
                ),
                'content' => array(
                    'body' => $description,
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

        $xml = simplexml_load_string($response);

        foreach ($xml->channel->item as $item) {
            if (!isset($item->pubDate) || !isset($item->title) || !isset($item->description)
                || !isset($item->link) || strpos($item->link, 'collection') !== false) {
                continue;
            }

            $this->setItem($item);
            $this->save();
        }
    }
}
