<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\MediaDTO;
use Contentful\Core\Resource\ResourceArray;
use Contentful\Delivery\Client;
use Contentful\Delivery\ClientOptions;
use Contentful\Delivery\Query;
use Contentful\Delivery\Resource\Entry;
use Contentful\RichText\Node\Document;
use Contentful\RichText\Node\EmbeddedEntryBlock;
use Contentful\RichText\Node\Hyperlink;
use Contentful\RichText\Node\Paragraph;
use Contentful\RichText\Node\Text;
use Contentful\RichText\Renderer;

class ContentfulService
{
    private $client;
    private $richTextRenderer;

    public function __construct(
        string $contentfulDeliveryApiKey,
        string $contentfulSpaceId,
        string $contentfulEnvironment
    ) {
        $options = ClientOptions::create()
            ->withHttpClient((new \GuzzleHttp\Client()))
        ;
        $this->client = new Client(
            $contentfulDeliveryApiKey,
            $contentfulSpaceId,
            $contentfulEnvironment,
            $options
        );
        $this->richTextRenderer = new Renderer();
    }

    public function getRichTextContent(Document $contentField): string
    {
        $content = '';
        if ($contentField !== null) {
            foreach ($contentField->jsonSerialize()['content'] as $item) {
                switch ($item->getType()) {
                    case 'embedded-asset-block':
                        $content .= '<div><img class="img-rich-text mw-100" src="'.$item->getAsset()->getFile()->getUrl().'" alt="'.$item->getAsset()->getTitle().'" /><p class="img-description">'.$item->getAsset()->getDescription().'</p></div>';

                        break;
                    case 'paragraph':
                        $content .= $this->transformItemToEmbed($item);

                        break;
                    case 'embedded-entry-block':
                        $content .= '<div>'.$this->transformItemToTable($item).'</div>';

                        break;
                    default:
                        $content .= $this->richTextRenderer->render($item);
                }
            }
        }

        return $content;
    }

    public function getEntryById($entryId): Entry
    {
        return $this->client->getEntry($entryId);
    }

    public function getEntries(Query $query): ResourceArray
    {
        return $this->client->getEntries($query);
    }

    // TODO getEntriesByQuery with more options to replace the 3 functions down.
    public function getEntriesByContentType(string $contentType, int $limit = null): ResourceArray
    {
        $query = (new Query())
            ->setContentType($contentType)
            ->setLimit($limit)
        ;

        return $this->client->getEntries($query);
    }

    public function getEntriesByField(string $contentType, string $field, string $value = null, int $limit = null): ResourceArray
    {
        $query = (new Query())
            ->setContentType($contentType)
            ->where($field, $value)
            ->setLimit($limit)
        ;

        return $this->client->getEntries($query);
    }

    public function getEntriesByOrder(string $contentType, string $field, bool $reverse = false, int $limit = null): ResourceArray
    {
        $query = (new Query())
            ->setContentType($contentType)
            ->orderBy($field, $reverse)
            ->setLimit($limit)
        ;

        return $this->client->getEntries($query);
    }

    public function getContentfulMedia(Entry $entry, string $attribute, int $widthLimit = null): MediaDTO
    {
        $asset      = $entry->get($attribute);
        $mediaDTO   = new MediaDTO();

        if (is_array($asset)){
            foreach ($asset as $item) {
                $mediaDTO->url      = $item->getFile()->getUrl();
                $mediaDTO->fileName = $item->getFile()->getFileName();
            }
        } else {
            $mediaDTO->url      = $asset->getFile()->getUrl();
            $mediaDTO->fileName = $asset->getFile()->getFileName();
        }

        return $mediaDTO;
    }

    /**
     * The map variable is the array containing the translations of each item received in the checkbox array
     */
    public function transformCheckboxToString(array $entry, array $map = null): string
    {
        $content = '';

        if ($map !== null) {
            foreach ($entry as $item) {
                if (count($entry) === 1) {
                    $content .= "{$map[$item]}";
                } else {
                    $content .= "{$map[$item]} ";
                }
            }
        } else {
            foreach ($entry as $item) {
                if (count($entry) === 1) {
                    $content .= "{$item}";
                } else {
                    $content .= "{$item} ";
                }
            }
        }

        return $content;
    }

    // Allows to use a link to a video in an iframe or embed code
    private function transformItemToEmbed(
        Paragraph $item
    ) {
        $content = '';

        $paragraph = $item->getContent();

        /** @var Hyperlink|Text $itemParagraph */
        foreach ($paragraph as $itemParagraph) {
            if ('hyperlink' === $itemParagraph->getType()) {
                $hyperlink = $itemParagraph->getUri();
                if (false !== strstr($hyperlink, 'youtube')) {
                    $idYouTubeVideo = substr(strrchr($hyperlink, '='), 1);
                    $content .=
                        '<div class="video-responsive" align="center"><iframe width="560" height="315" src="https://www.youtube.com/embed/'.$idYouTubeVideo.'"frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
                } else {
                    /** @var Text $linkLabelText */
                    $linkLabelText = $itemParagraph->getContent()[0];

                    if (null !== $linkLabelText) {
                        $linkLabel = $linkLabelText->getValue();
                    }

                    $content .= '<a href='.$hyperlink.' target="_blank"><u>'.($linkLabel ?? $hyperlink).'</u></a>';
                }
            } elseif (false !== strstr($itemParagraph->getValue(), 'gist.github.com')) {
                $content .= '<div class="gist" data-src="'.$itemParagraph->getValue().'"></div>';
            } else {
                $content .= $this->richTextRenderer->render($itemParagraph);
            }
        }

        return $content;
    }

    private function transformItemToTable(EmbeddedEntryBlock $item): string
    {
        $entry = $item->getEntry();
        $table = $entry['table']['table'];

        $thead       = $table[0]['thead'];
        $tbody       = $table[1]['tbody'];
        $theadString = '<thead><tr>';

        foreach ($thead as $h) {
            $theadString .= '<th scope="col">'.$h.'</th>';
        }

        $theadString .= '</tr></thead>';

        $tbodyString = '<tbody>';
        foreach ($tbody as $row) {
            $tbodyString .= '<tr>';
            foreach ($row as $item) {
                $tbodyString .= '<td>'.$item.'</td>';
            }
            $tbodyString .= '</tr>';
        }
        $tbodyString .= '</tbody>';

        return '<table class="table table-bordered table-sm table-responsive">'.$theadString.$tbodyString.'</table>';
    }
}