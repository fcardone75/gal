<?php

namespace App\Twig;

use App\Service\FileTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class AppExtension extends AbstractExtension
{
    use FileTrait;

    public function getFilters()
    {
        return [
            new TwigFilter('size_units', [$this, 'formatSizeUnits']),
            new TwigFilter('base64_img_url', [$this, 'base64ImgUrl']),
            new TwigFilter('format_currency', [$this, 'formatCurrency']),
        ];
    }

    public function formatSizeUnits($bytesSize): string
    {
        return $this->getFormattedSizeUnits($bytesSize);
    }

    public function base64ImgUrl($url): string
    {
        $type = pathinfo($url, PATHINFO_EXTENSION);
        $data = file_get_contents($url, false, stream_context_create([
                "ssl" => [
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ]
            ])
        );
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    public function formatCurrency($value): string
    {
        if ($value) {
            $value = '€ ' . number_format($value, 2, ',', '.');
        } else {
            $value = '--';
        }
        return $value;
    }
}
