<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsSourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'AP News',
                'url' => 'https://apnews.com/',
                'feed_url' => 'https://apnews.com/apf-topnews',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'BBC News',
                'url' => 'https://www.bbc.com/news',
                'feed_url' => 'https://feeds.bbci.co.uk/news/world/rss.xml',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'CNN',
                'url' => 'https://edition.cnn.com/',
                'feed_url' => 'http://rss.cnn.com/rss/edition_world.rss',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'Al Jazeera',
                'url' => 'https://www.aljazeera.com/',
                'feed_url' => 'https://www.aljazeera.com/xml/rss/all.xml',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'The Guardian',
                'url' => 'https://www.theguardian.com/',
                'feed_url' => 'https://www.theguardian.com/world/rss',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'The New York Times',
                'url' => 'https://www.nytimes.com/',
                'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/World.xml',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'Xinhua (ซินหัว)',
                'url' => 'http://www.xinhuanet.com/english/',
                'feed_url' => 'http://www.xinhuanet.com/english/rss/worldrss.xml',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'China Daily',
                'url' => 'https://www.chinadaily.com.cn/',
                'feed_url' => 'https://www.chinadaily.com.cn/rss/china_rss.xml',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'Reuters',
                'url' => 'https://www.reuters.com/',
                'feed_url' => 'https://feeds.reuters.com/Reuters/worldNews',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'world',
            ],
            [
                'name' => 'Thai PBS',
                'url' => 'https://www.thaipbs.or.th/',
                'feed_url' => 'https://www.thaipbs.or.th/rss/news',
                'locale' => 'th',
                'fetch_type' => 'rss',
                'category' => 'general',
            ],
            [
                'name' => 'Bangkok Post',
                'url' => 'https://www.bangkokpost.com/',
                'feed_url' => 'https://www.bangkokpost.com/rss/topstories.xml',
                'locale' => 'en',
                'fetch_type' => 'rss',
                'category' => 'general',
            ],
            [
                'name' => 'MGR Online (ผู้จัดการออนไลน์)',
                'url' => 'https://mgronline.com/',
                'feed_url' => 'https://www.mgronline.com/rss/live.xml',
                'locale' => 'th',
                'fetch_type' => 'rss',
                'category' => 'general',
            ],
            [
                'name' => 'Bloomberg Technology (API demo)',
                'url' => 'https://newsapi.org/v2/top-headlines',
                'feed_url' => 'https://newsapi.org/v2/top-headlines?sources=techcrunch',
                'locale' => 'en',
                'fetch_type' => 'api',
                'category' => 'technology',
                'config' => [
                    'api_key_header' => 'X-Api-Key',
                    'params' => ['language' => 'en'],
                ],
            ],
        ];

        foreach ($sources as $source) {
            $slug = Str::slug($source['name']).'-'.Str::lower(Str::random(4));
            NewsSource::updateOrCreate(
                ['name' => $source['name']],
                [
                    ...$source,
                    'slug' => $slug,
                    'cron_expression' => '0 * * * *',
                    'is_active' => true,
                ]
            );
        }
    }
}
