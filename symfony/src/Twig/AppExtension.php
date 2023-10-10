<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('letterize', [$this, 'explodeString']),
        ];
    }

    public function explodeString($string)
    {
        $split = function ($str, $len = 1) {
            $arr    = [];
            $length = mb_strlen($str, 'UTF-8');
            for ($i = 0; $i < $length; $i += $len) {
                $arr[] = mb_substr($str, $i, $len, 'UTF-8');
            }

            return $arr;
        };
        $blank_to_nbsp = function ($char) { return ' ' == $char ? '&nbsp;' : $char; };

        return '<span>'.implode('</span><span>', array_map($blank_to_nbsp, $split($string))).'</span>';
    }
}
