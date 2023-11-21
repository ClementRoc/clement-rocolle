<?php

declare(strict_types=1);

namespace App\Exception;

class HydrateObjectContentfulException extends \Exception
{
    public function __construct(
        string $attribute,
        string $contentName,
        string $contentfulId
    ) {
        parent::__construct("{$attribute} not found for Entity contentful with id {$contentfulId} in {$contentName}");
    }
}
