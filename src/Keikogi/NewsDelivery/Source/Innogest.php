<?php

namespace Keikogi\NewsDelivery\Source;

use Keikogi\NewsDelivery\Core\BaseSource;
use Keikogi\NewsDelivery\Core\SourceInterface;

class Innogest extends BaseSource implements SourceInterface
{
    const BASE_URL = 'http://api.innogest.ru/api/v3/amobile/news?limit=3';

    const USER_AGENT = 'Mozilla/5.0 (Linux; U; Android 4.0.3; ko-kr; LG-L160L Build/IML74K) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30';

    public function url()
    {
        return 'news/innogest-' . $this->getItem()->nid;
    }

    public function type()
    {
        return 'news';
    }

    public function prepare(&$flowItem, &$typeItem)
    {
        $image = '';
        $description = '';
        $newLink = $this->url();
        $body = $this->getItem()->body;
        $title = $this->getItem()->title;
        $pubDate = time();

        preg_match('/\<em\>(?P<description>.*)\<\/em\>/i', $body, $matches);
        if (isset($matches['description'])) {
            $description = strip_tags($matches['description']);
        }

        if (isset($this->getItem()->img_url)) {
            $image = $this->getItem()->img_url;
        }

        $flowItem = array(
            'sub_type' => 'innogest',
            'url' => $newLink,
            'title' => $title,
            'document_type' => $this->type(),
            'published_at' => $pubDate,
            'source' => array(
                'name' => 'Innogest',
                'quote' => $description,
            ),
        );

        if ($image) {
            $flowItem['image']['small_url'] = $image;
        }

        $typeItem = array(
            'sub_type' => 'innogest',
            'root' => array(
                'url' => $newLink,
                'title' => $title,
                'document_type' => $this->type(),
                'published_at' => $pubDate,
                'source' => array(
                    'name' => 'Innogest',
                ),
                'content' => array(
                    'body' => $body,
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

        $newsList = json_decode($response);

        foreach ($newsList as $news) {
            if ($news->type == 'news_agency') {
                continue;
            }

            $this->setItem($news);
            $this->save();
        }
    }
}
