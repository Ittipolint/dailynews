<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use Illuminate\Support\Str;

/**
 * Classifies a news headline (optionally plus summary) into a single category
 * code by scoring known keywords in both English and Thai. This is used during
 * ingestion so each stored article gets its *actual* category from its content,
 * instead of inheriting the source's configured category.
 */
class CategoryClassifier
{
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
     */
    public function classify(?string $title, ?string $summary = null): string
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