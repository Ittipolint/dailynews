<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Services\Llm\LlmService;
use Illuminate\Support\Str;

/**
 * Classifies a news headline (optionally plus summary) into a single category
 * code. When an LLM is configured (services.category_classifier.llm_enabled),
 * the LLM is asked to pick the category; otherwise (or on LLM failure) a
 * keyword-based scorer in both English and Thai is used. This runs during
 * ingestion so each stored article gets its *actual* category from its content,
 * instead of inheriting the source's configured category.
 */
class CategoryClassifier
{
    private const CATEGORIES = [
        'technology', 'business', 'sports', 'world', 'politics',
        'science', 'health', 'entertainment', 'general',
    ];

    /**
     * In-request cache so repeated classify() calls for the same text (e.g. the
     * same article processed by both ingestion and reclassify) only use one
     * LLM request.
     */
    private array $llmCache = [];

    public function __construct(private readonly ?LlmService $llm = null) {}

    /**
     * Category code => list of trigger words (lowercased). Longer, more specific
     * words are weighted more heavily so e.g. "ชิป" beats a generic "บริษัท".
     */
    private const KEYWORDS = [
        'technology' => [
            'technology', 'technologies', 'tech', 'software', 'smartphone', 'artificial intelligence',
            'machine learning', 'chip', 'semiconductor', 'processor', 'cpu', 'gpu', 'startup',
            'start-up', 'application', 'algorithm', 'cloud', 'cyber', 'digital', 'internet',
            'robotics', 'robot', 'gadget', 'laptop', '5g', 'data center', 'silicon', 'big data',
            'encryption', 'metaverse', 'gaming', 'developer', 'code', 'openai', 'chatgpt',
            'google', 'apple', 'microsoft', 'samsung', 'huawei', 'nvidia', 'tesla',
            'เทคโนโลยี', 'ไอที', 'สมาร์ตโฟน', 'สมาร์ทโฟน', 'คอมพิวเตอร์', 'ซอฟต์แวร์',
            'ปัญญาประดิษฐ์', 'เอไอ', 'ชิป', 'เซมิคอนดักเตอร์', 'สตาร์ตอัป', 'แอป', 'แอพ',
            'คลาวด์', 'ไซเบอร์', 'ดิจิทัล', 'อินเทอร์เน็ต', 'หุ่นยนต์', 'มือถือ', 'นวัตกรรม',
            'กูเกิล', 'ไมโครซอฟต์', 'แอปเปิล', 'เกม', 'เทค',
        ],
        'business' => [
            'business', 'economy', 'economic', 'stock', 'market', 'shares', 'invest', 'investment',
            'investor', 'finance', 'financial', 'bank', 'banking', 'gdp', 'inflation', 'trade',
            'trading', 'export', 'import', 'currency', 'baht', 'dollar', 'oil price', 'gold price',
            'company', 'corporate', 'ceo', 'revenue', 'profit', 'earnings', 'wall street', 'nasdaq',
            'dow jones', 'fed', 'central bank', 'interest rate', 'loan', 'debt', 'cryptocurrency',
            'bitcoin', 'stock market', 'ตลาดหุ้น', 'หุ้น', 'เศรษฐกิจ', 'การเงิน', 'ธนาคาร',
            'จีดีพี', 'เงินเฟ้อ', 'การค้า', 'บริษัท', 'นักลงทุน', 'เงินลงทุน', 'ราคาน้ำมัน',
            'ราคาทอง', 'เงินบาท', 'สกุลเงิน', 'อัตราดอกเบี้ย', 'กำไร', 'ขาดทุน', 'ค้าขาย',
        ],
        'sports' => [
            'sport', 'sports', 'football', 'soccer', 'olympic', 'league', 'tennis', 'golf',
            'basketball', 'championship', 'world cup', 'premier league', 'cricket', 'boxing',
            'marathon', 'racing', 'f1', 'formula one', 'badminton', 'swimming', 'medal',
            'นักกีฬา', 'กีฬา', 'ฟุตบอล', 'บอล', 'โอลิมปิก', 'เทนนิส', 'กอล์ฟ', 'บาสเกตบอล',
            'วอลเลย์', 'มวย', 'วิ่ง', 'เหรียญ', 'แชมป์', 'แบดมินตัน', 'ว่ายน้ำ', 'สนามกีฬา',
        ],
        'world' => [
            'world', 'international', 'global', 'foreign', 'united nations', 'nato', 'diplomat',
            'diplomacy', 'embassy', 'summit', 'geopolitic', 'overseas', 'europe', 'america',
            'china', 'russia', 'ukraine', 'war', 'conflict', 'bilateral', 'treaty',
            'โลก', 'ต่างประเทศ', 'นานาชาติ', 'สหประชาชาติ', 'นาโต', 'สงคราม', 'ความขัดแย้ง',
            'ยุโรป', 'อเมริกา', 'จีน', 'รัสเซีย', 'ยูเครน', 'ระหว่างประเทศ', 'สหรัฐ', 'อังกฤษ',
            'เยอรมนี', 'ฝรั่งเศส', 'สนธิสัญญา',
        ],
        'politics' => [
            'politics', 'political', 'parliament', 'election', 'prime minister', 'president',
            'government', 'senate', 'cabinet', 'minister', 'lawmaker', 'bill', 'vote', 'democracy',
            'policy', 'constitution', 'opposition', 'protest', 'impeach',
            'การเมือง', 'รัฐ', 'เลือกตั้ง', 'นายก', 'สภา', 'ครม', 'มติ', 'นโยบาย', 'กฎหมาย',
            'ข้าราชการ', 'กกต', 'ศาลรัฐธรรมนูญ', 'สมาชิกสภา', 'วุฒิสภา', 'ประธานาธิบดี',
            'รัฐมนตรี', 'ผู้ว่าฯ', 'ประชาธิปไตย', 'แพทยสภา',
        ],
        'science' => [
            'science', 'scientific', 'space', 'nasa', 'research', 'discovery', 'physics',
            'astronomy', 'quantum', 'particle', 'genome', 'moon', 'mars', 'planet', 'galaxy',
            'astronaut', 'orbit', 'telescope', 'climate', 'experiment', 'laboratory',
            'วิทยาศาสตร์', 'อวกาศ', 'นาซ่า', 'ดาวเคราะห์', 'ดวงจันทร์', 'ดาวอังคาร', 'ดาราศาสตร์',
            'ฟิสิกส์', 'เคมี', 'ชีววิทยา', 'นักวิจัย', 'วิจัย', 'สภาพอากาศ', 'การทดลอง',
        ],
        'health' => [
            'health', 'hospital', 'disease', 'virus', 'vaccine', 'covid', 'coronavirus', 'cancer',
            'medicine', 'medical', 'doctor', 'patient', 'surgery', 'treatment', 'pandemic',
            'nutrition', 'mental health', 'symptom', 'clinic',
            'สุขภาพ', 'โรงพยาบาล', 'โรค', 'ไวรัส', 'วัคซีน', 'โควิด', 'มะเร็ง', 'หมอ', 'ยา',
            'การรักษา', 'ผู้ป่วย', 'ผ่าตัด', 'สุขภาพจิต', 'อาหาร', 'คลินิก',
        ],
        'entertainment' => [
            'entertainment', 'movie', 'film', 'cinema', 'music', 'concert', 'celebrity', 'singer',
            'actor', 'actress', 'show', 'tv', 'series', 'album', 'award', 'netflix', 'hollywood',
            'streaming', 'festival', 'band', 'drama', 'pop star', 'box office',
            'บันเทิง', 'ภาพยนตร์', 'หนัง', 'เพลง', 'คอนเสิร์ต', 'นักร้อง', 'ดารา', 'นักแสดง',
            'ซีรีส์', 'ละคร', 'อัลบั้ม', 'ฮอลลีวูด', 'สตรีมมิง', 'เทศกาล', 'วงดนตรี',
        ],
    ];

    /**
     * Score an article's title (and optional summary/body) and return the best
     * matching category code. Falls back to "general" when nothing matches.
     * When an LLM is enabled, the LLM answers first and the keyword scorer is
     * only used if the LLM is unavailable or returns an invalid category.
     */
    public function classify(?string $title, ?string $summary = null): string
    {
        if ($this->llmEnabled()) {
            $category = $this->classifyWithLlm($title, $summary);

            if ($category !== null) {
                return $category;
            }
        }

        return $this->classifyWithKeywords($title, $summary);
    }

    /**
     * Ask the configured LLM to pick a category, returning null when it is
     * unavailable, errors, or returns an invalid category code.
     */
    protected function classifyWithLlm(?string $title, ?string $summary): ?string
    {
        $text = trim(($title ?? '').' '.($summary ?? ''));

        if ($text === '') {
            return null;
        }

        $cacheKey = Str::lower(mb_substr($text, 0, 400));

        if (array_key_exists($cacheKey, $this->llmCache)) {
            return $this->llmCache[$cacheKey];
        }

        try {
            $answer = $this->llm?->generate(
                'You are a news categorization engine. Classify the following news item into exactly one of these categories: '
                .implode(', ', self::CATEGORIES).'. '
                .'Respond with ONLY a JSON object like {"category": "technology"} and nothing else. '
                ."News title: {$title}\n"
                .'News summary: '.($summary ?? 'n/a')."\n",
                [
                    'temperature' => 0,
                    'max_tokens' => 20,
                    'retries' => 1,
                ]
            );

            $category = $this->parseLlmCategory($answer);

            return $this->llmCache[$cacheKey] = $category;
        } catch (\Throwable $e) {
            // LLM unavailable (quota, network...). Remember and fall through to
            // the keyword scorer.
            $this->llmCache[$cacheKey] = null;

            return null;
        }
    }

    protected function parseLlmCategory(?string $answer): ?string
    {
        $answer = trim((string) $answer);

        if ($answer === '') {
            return null;
        }

        $decoded = json_decode($answer, true);
        $candidate = is_array($decoded) ? trim((string) ($decoded['category'] ?? '')) : '';

        if ($candidate === '' && preg_match('/\b('.implode('|', self::CATEGORIES).')\b/i', $answer, $m)) {
            $candidate = Str::lower($m[1]);
        }

        $candidate = Str::lower($candidate);

        return in_array($candidate, self::CATEGORIES, true) ? $candidate : null;
    }

    protected function llmEnabled(): bool
    {
        if (! config('services.category_classifier.llm_enabled', false)) {
            return false;
        }

        return $this->llm !== null && $this->llm->isAvailable();
    }

    protected function classifyWithKeywords(?string $title, ?string $summary = null): string
    {
        $title = mb_strtolower(trim((string) $title), 'UTF-8');
        $summary = mb_strtolower(trim((string) $summary), 'UTF-8');

        $scores = [];

        foreach (self::KEYWORDS as $category => $keywords) {
            $score = 0;

            foreach ($keywords as $word) {
                $word = trim($word);
                $wordLen = mb_strlen($word, 'UTF-8');

                if ($wordLen === 0 || $wordLen < 2) {
                    continue;
                }

                if ($title !== '' && $this->contains($title, $word)) {
                    // Title matches count double. Long specific words weigh more.
                    $score += ($wordLen >= 6 ? 3 : 2);
                } elseif ($summary !== '' && $this->contains($summary, $word)) {
                    $score += ($wordLen >= 6 ? 2 : 1);
                }
            }

            if ($score > 0) {
                $scores[$category] = $score;
            }
        }

        if ($scores === []) {
            return 'general';
        }

        arsort($scores);

        return array_key_first($scores);
    }

    private function contains(string $haystack, string $needle): bool
    {
        // Thai/CJK scripts are written without spaces, so a bare substring
        // search is the right primitive for them (English is covered too).
        return Str::contains($haystack, $needle);
    }
}
