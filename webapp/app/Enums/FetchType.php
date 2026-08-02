<?php

declare(strict_types=1);

namespace App\Enums;

enum FetchType: string
{
    case RSS = 'rss';
    case API = 'api';
    case CRAWL = 'crawl';

    public function label(): string
    {
        return match ($this) {
            self::RSS => 'RSS / Atom',
            self::API => 'API',
            self::CRAWL => 'Web Crawling',
        };
    }
}
