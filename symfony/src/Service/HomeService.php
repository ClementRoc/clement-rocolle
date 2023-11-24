<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PictureDTO;
use App\DTO\ExperienceDTO;
use App\DTO\PortfolioDTO;
use App\Exception\HydrateObjectContentfulException;
use Contentful\Delivery\Resource\Entry;

class HomeService
{
    private $contentfulService;

    public function __construct(
        ContentfulService $contentfulService
    ) {
        $this->contentfulService = $contentfulService;
    }

    public function getExperiences(): array
    {
        $experiences    = $this->contentfulService->getEntriesByContentType('experience');
        $experiencesDTO = [];

        if (current($experiences->getIterator()) !== null) {
            foreach ($experiences as $experience) {
                $experiencesDTO[] = $this->hydrateExperienceDTO($experience);
            }
        }

        return $experiencesDTO;
    }

    public function getPortfolios(): array
    {
        $portfolios    = $this->contentfulService->getEntriesByContentType('portfolio');
        $portfoliosDTO = [];

        if (current($portfolios->getIterator()) !== null) {
            foreach ($portfolios as $portfolio) {
                $portfoliosDTO[] = $this->hydratePortfolioDTO($portfolio);
            }
        }

        return $portfoliosDTO;
    }

    private function hydrateExperienceDTO(Entry $item): ExperienceDTO
    {
        $experienceDTO = new ExperienceDTO();

        // dd($this->contentfulService->getRichTextContent($item->get('presentation')));

        $experienceDTO->date         = $this->checkAttribute($item, 'date');
        $experienceDTO->title        = $this->checkAttribute($item, 'title');
        $experienceDTO->society      = $this->checkAttribute($item, 'society');
        $experienceDTO->societyLink  = $this->checkAttribute($item, 'societyLink');
        $experienceDTO->presentation = $this->contentfulService->getRichTextContent($this->checkAttribute($item, 'presentation'));
        $experienceDTO->tags         = $this->checkAttribute($item, 'tags');

        return $experienceDTO;
    }

    private function hydratePortfolioDTO(Entry $item): PortfolioDTO
    {
        $portfolioDTO = new PortfolioDTO();

        $portfolioDTO->image        = $this->checkPicture($item, 'image');
        $portfolioDTO->title        = $this->checkAttribute($item, 'title');
        $portfolioDTO->society      = $this->checkAttribute($item, 'society');
        $portfolioDTO->societyLink  = $this->checkAttribute($item, 'societyLink');
        $portfolioDTO->presentation = $this->contentfulService->getRichTextContent($this->checkAttribute($item, 'presentation'));
        $portfolioDTO->tags         = $this->checkAttribute($item, 'tags');

        return $portfolioDTO;
    }

    /**
     * @throws HydrateObjectContentfulException
     */
    private function checkAttribute(Entry $entry, string $attribute)
    {
        if (!$entry->get($attribute)) {
            throw new HydrateObjectContentfulException(
                $attribute,
                $entry->getSystemProperties()->getContentType()->getName(),
                $entry->getSystemProperties()->getId()
            );
        }

        return $entry->get($attribute);
    }

    /**
     * @throws HydrateObjectContentfulException
     */
    private function checkPicture(Entry $entry, string $attribute, int $widthLimit = null): PictureDTO
    {
        if (!$entry->get($attribute)) {
            throw new HydrateObjectContentfulException(
                $attribute,
                $entry->getSystemProperties()->getContentType()->getName(),
                $entry->getSystemProperties()->getId()
            );
        }

        return $this->contentfulService->getContentfulPicture($entry, $attribute, $widthLimit);
    }
}