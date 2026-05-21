<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportWhatsapp extends Command
{
    protected $signature = 'whatsapp:import
        {path : Path to the unzipped WhatsApp export folder}
        {--sender=JorEption : Sender name to filter to (the admin)}
        {--dry-run : Parse and report, but do not create products}
        {--limit=0 : Limit to first N products (0 = no limit)}
        {--with-image-only : Skip products without a matched image}
        {--activate : Create products as is_active=true (default is draft)}';

    protected $description = 'Import WhatsApp chat export into Products';

    private array $imagesByDate = [];

    public function handle(): int
    {
        $path = rtrim($this->argument('path'), "\\/");

        if (! is_dir($path)) {
            $this->error("Folder not found: {$path}");
            return self::FAILURE;
        }

        $txt = $this->findChatTxt($path);
        if (! $txt) {
            $this->error("Couldn't find a .txt file in {$path}");
            return self::FAILURE;
        }

        $this->info("Chat file:    {$txt}");
        $this->info('Sender:       ' . $this->option('sender'));
        $this->info('Dry run:      ' . ($this->option('dry-run') ? 'yes' : 'no'));
        $this->info('Activate:     ' . ($this->option('activate') ? 'yes' : 'no — drafts'));
        $this->newLine();

        $this->indexImages($path);
        $this->info('Indexed ' . array_sum(array_map('count', $this->imagesByDate)) . ' images across ' . count($this->imagesByDate) . ' dates.');
        $this->newLine();

        $messages = $this->parseMessages($txt);
        $this->info('Parsed ' . count($messages) . ' chat messages.');

        $adminMessages = array_values(array_filter(
            $messages,
            fn ($m) => $m['sender'] === $this->option('sender'),
        ));
        $this->info('Filtered to ' . count($adminMessages) . " messages from {$this->option('sender')}.");

        $products = $this->groupIntoProducts($adminMessages);
        $this->info('Detected ' . count($products) . ' product posts.');
        $this->newLine();

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $products = array_slice($products, 0, $limit);
            $this->warn("Limiting to first {$limit} products.");
        }

        $created = 0;
        $withImage = 0;
        $rows = [];

        foreach ($products as $i => $p) {
            $price = $this->extractPrice($p['body']);
            $compareAtPrice = $this->extractCompareAtPrice($p['body']);
            $stock = $this->extractStock($p['body']);
            [$name, $description] = $this->extractNameAndDescription($p['body']);

            if ($price === null) {
                $this->warn('  ✗ skipped #' . ($i + 1) . ' (no price found)');
                continue;
            }

            $imagePath = $this->matchImage($p, $path);
            if ($imagePath) {
                $withImage++;
            } elseif ($this->option('with-image-only')) {
                continue;
            }

            $rows[] = [
                Str::limit($name, 40),
                $price,
                $stock,
                $imagePath ? '✓' : '—',
                $p['date'],
            ];

            if (! $this->option('dry-run')) {
                Product::create([
                    'name_en' => $this->clean($name),
                    'name_ar' => $this->clean($name),
                    'description_ar' => $this->clean($description),
                    'description_en' => null,
                    'price' => $price,
                    'compare_at_price' => $compareAtPrice,
                    'stock' => $stock,
                    'image_path' => $imagePath,
                    'is_active' => (bool) $this->option('activate'),
                ]);
                $created++;
            }
        }

        $this->newLine();
        $this->table(['Name', 'Price', 'Stock', 'Image', 'Date'], $rows);
        $this->newLine();

        $this->info("Products parsed:        " . count($rows));
        $this->info("With matched image:     {$withImage}");
        $this->info("Created in DB:          " . ($this->option('dry-run') ? '0 (dry-run)' : $created));

        return self::SUCCESS;
    }

    private function findChatTxt(string $path): ?string
    {
        foreach (File::files($path) as $file) {
            if (strtolower($file->getExtension()) === 'txt') {
                return $file->getPathname();
            }
        }
        return null;
    }

    private function indexImages(string $path): void
    {
        foreach (File::files($path) as $file) {
            $name = $file->getFilename();
            if (! preg_match('/^IMG-(\d{4})(\d{2})(\d{2})-WA(\d+)\./', $name, $m)) {
                continue;
            }
            $date = "{$m[1]}-{$m[2]}-{$m[3]}";
            $seq = (int) $m[4];
            $this->imagesByDate[$date][$seq] = $file->getPathname();
        }
        foreach ($this->imagesByDate as $d => &$imgs) {
            ksort($imgs);
        }
    }

    private function parseMessages(string $txt): array
    {
        $lines = file($txt, FILE_IGNORE_NEW_LINES);
        $messages = [];
        $current = null;
        $re = '/^(\d{1,2})\/(\d{1,2})\/(\d{2,4}),\s*(\d{1,2}):(\d{2})\s*([AP]M)\s*-\s*(.+?):\s?(.*)$/u';

        foreach ($lines as $line) {
            if (preg_match($re, $line, $m)) {
                if ($current) {
                    $messages[] = $current;
                }
                $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                $current = [
                    'date' => sprintf('%s-%02d-%02d', $year, (int) $m[1], (int) $m[2]),
                    'time' => sprintf('%02d:%02d %s', (int) $m[4], (int) $m[5], $m[6]),
                    'datetime' => null,
                    'sender' => trim($m[7]),
                    'body' => $m[8],
                ];
                try {
                    $current['datetime'] = Carbon::createFromFormat(
                        'Y-m-d h:i A',
                        $current['date'] . ' ' . $current['time'],
                    );
                } catch (\Throwable) {
                    $current['datetime'] = null;
                }
            } elseif ($current) {
                $current['body'] .= "\n" . $line;
            }
        }
        if ($current) {
            $messages[] = $current;
        }
        return $messages;
    }

    private function groupIntoProducts(array $messages): array
    {
        $products = [];
        $pendingMedia = [];

        foreach ($messages as $msg) {
            $body = trim($msg['body']);

            if ($body === '' || $body === '<Media omitted>'
                || preg_match('/^[A-Z]{3}-\d{8}-WA\d+\.\w+\s*\(file attached\)/', $body)) {
                $pendingMedia[] = $msg;
                continue;
            }

            $hasPrice = $this->extractPrice($body) !== null;

            if ($hasPrice) {
                $products[] = [
                    'date' => $msg['date'],
                    'datetime' => $msg['datetime'],
                    'body' => $body,
                    'media_count' => count($pendingMedia),
                ];
                $pendingMedia = [];
            } else {
                $pendingMedia = [];
            }
        }

        return $products;
    }

    private function extractPrice(string $body): ?float
    {
        $patterns = [
            '/(?:السعر|سعر\s+العرض)\s*[:\-]?\s*\*?\s*(\d+(?:\.\d+)?)/u',
            '/\*\s*(\d+(?:\.\d+)?)\s*(?:دينار|دنانير|JD|jd)\s*\*/u',
            '/(\d+(?:\.\d+)?)\s*(?:دينار|دنانير|JD|jd)\b/u',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $body, $m)) {
                return (float) $m[1];
            }
        }
        return null;
    }

    private function extractCompareAtPrice(string $body): ?float
    {
        // Common WhatsApp patterns for the reference / original price:
        //   "Online 172$"   "Online $130"   "*سعره بالسوق 127 دينار*"
        $patterns = [
            '/Online\s*\$?\s*(\d+(?:\.\d+)?)\s*\$?/iu',
            '/سعره\s+بالسوق\s*\*?\s*(\d+(?:\.\d+)?)/u',
            '/سعرها\s+بالسوق\s*\*?\s*(\d+(?:\.\d+)?)/u',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $body, $m)) {
                $value = (float) $m[1];
                if ($value > 0) {
                    return $value;
                }
            }
        }
        return null;
    }

    private function extractStock(string $body): int
    {
        if (preg_match('/قطعة\s+واحدة\s+فقط|باقي\s+(?:عدد\s+)?1\b|متوفر\s+1\b/u', $body)) {
            return 1;
        }
        if (preg_match('/متوفر\s+(\d+)\s*(?:حبة|قطعة)?/u', $body, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/باقي\s+(?:عدد\s+)?(\d+)/u', $body, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/جددنا\s+الكمية\s+(\d+)/u', $body, $m)) {
            return (int) $m[1];
        }
        return 1;
    }

    private function extractNameAndDescription(string $body): array
    {
        $lines = array_map('trim', explode("\n", $body));
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

        $skipPatterns = [
            '/^\*?\s*(?:السعر|سعر\s+العرض|سعره\s+بالسوق)/u',
            '/^\*?\s*(?:Online|متوفر|قطعة\s+واحدة|باقي|جددنا)/u',
            '/^\s*<Media omitted>\s*$/u',
            '/^[A-Z]{3}-\d{8}-WA\d+\./u',
            '/^\s*(?:🔥|🇩🇪|🇬🇧|🇺🇸|🇦🇺|🇨🇳|🇫🇷|🇪🇸|🇮🇹|🇯🇵|🇰🇷|🆕|💯|⭐)+\s*$/u',
            '/^\s*(?:Germany|UK|Uk|USA|Usa|Australia|France|Spain|Italy|Japan|Korea|China)\b[\s🇩🇪🇬🇧🇺🇸🇦🇺🇨🇳🇫🇷🇪🇸🇮🇹🇯🇵🇰🇷]*$/iu',
            '/^\s*[\*\s]*$/u',
        ];

        $name = null;
        foreach ($lines as $l) {
            $clean = trim($l, " \t*🔥💯⭐🆕");
            if ($clean === '') {
                continue;
            }
            $skip = false;
            foreach ($skipPatterns as $re) {
                if (preg_match($re, $clean)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }
            $name = $clean;
            break;
        }
        if ($name === null || $name === '') {
            $name = Str::limit($body, 60, '');
        }

        return [Str::limit($name, 120, ''), $body];
    }

    private function matchImage(array $product, string $exportPath): ?string
    {
        $date = $product['date'];
        if (! isset($this->imagesByDate[$date])) {
            return null;
        }

        if (! isset($this->cursorByDate[$date])) {
            $this->cursorByDate[$date] = 0;
        }

        $images = array_values($this->imagesByDate[$date]);
        if ($this->cursorByDate[$date] >= count($images)) {
            return null;
        }

        $source = $images[$this->cursorByDate[$date]];
        $advance = max(1, $product['media_count'] ?? 1);
        $this->cursorByDate[$date] += $advance;

        $targetDir = storage_path('app/public/products');
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $basename = basename($source);
        $dest = $targetDir . DIRECTORY_SEPARATOR . $basename;
        if (! $this->option('dry-run') && ! file_exists($dest)) {
            copy($source, $dest);
        }

        return 'products/' . $basename;
    }

    private array $cursorByDate = [];

    private function clean(?string $s): ?string
    {
        if ($s === null) {
            return null;
        }
        $s = (string) @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        return $s === false ? null : trim($s);
    }
}
