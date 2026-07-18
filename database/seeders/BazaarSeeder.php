<?php

namespace Database\Seeders;

use App\Models\BazaarNight;
use App\Models\BazaarTable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

/**
 * Seeds the 2026 season: 30 event nights (Thursdays + Fridays, 23 Jul – 30 Oct)
 * and the 100 tables from the venue floor plan.
 *
 * Idempotent — matches on event_date / table number, so re-running it updates
 * geometry without disturbing existing bookings.
 */
class BazaarSeeder extends Seeder
{
    private const SEASON_START = '2026-07-23';

    private const SEASON_END = '2026-10-30';

    private const TABLE_PRICE = 30;

    // Doors open earlier on Fridays.
    private const THURSDAY_OPENS = 18;

    private const FRIDAY_OPENS = 16;

    /**
     * Runs of tables as laid out on the plan.
     * from/count = the numbered range; x/y = top-left in SVG viewBox units;
     * dx/dy = step between consecutive tables in the run.
     */
    private const RUNS = [
        // Restaurant units along the top edge.
        ['from' => 1,  'count' => 12, 'x' => 300, 'y' => 70,  'dx' => 33, 'dy' => 0],
        // Right-hand column: Section D (13–18) above Section B (19–22).
        ['from' => 13, 'count' => 10, 'x' => 800, 'y' => 110, 'dx' => 0,  'dy' => 26],
        // Upper middle rows.
        ['from' => 23, 'count' => 10, 'x' => 405, 'y' => 250, 'dx' => 33, 'dy' => 0],
        ['from' => 33, 'count' => 15, 'x' => 240, 'y' => 283, 'dx' => 33, 'dy' => 0],
        // Left-hand column: Section B.
        ['from' => 48, 'count' => 6,  'x' => 185, 'y' => 320, 'dx' => 0,  'dy' => 26],
        // Central block.
        ['from' => 54, 'count' => 15, 'x' => 265, 'y' => 345, 'dx' => 33, 'dy' => 0],
        ['from' => 69, 'count' => 15, 'x' => 265, 'y' => 378, 'dx' => 33, 'dy' => 0],
        // Bottom row.
        ['from' => 84, 'count' => 13, 'x' => 232, 'y' => 445, 'dx' => 33, 'dy' => 0],
        // Angled cluster in the bottom-right corner of the lot.
        ['from' => 97, 'count' => 4,  'x' => 700, 'y' => 520, 'dx' => 18, 'dy' => -18, 'rotation' => -45],
    ];

    public function run(): void
    {
        $this->seedNights();
        $this->seedTables();
    }

    private function seedNights(): void
    {
        $period = CarbonPeriod::create(self::SEASON_START, self::SEASON_END);
        $count = 0;

        foreach ($period as $date) {
            if (! $date->isThursday() && ! $date->isFriday()) {
                continue;
            }

            $opens = $date->isThursday() ? self::THURSDAY_OPENS : self::FRIDAY_OPENS;

            BazaarNight::updateOrCreate(
                ['event_date' => $date->toDateString()],
                [
                    'starts_at' => $date->copy()->setTime($opens, 0),
                    // Runs until midnight, i.e. 00:00 the following day.
                    'ends_at' => $date->copy()->addDay()->startOfDay(),
                    'is_active' => true,
                ]
            );

            $count++;
        }

        $this->command?->info("Bazaar nights seeded: {$count}");
    }

    private function seedTables(): void
    {
        $count = 0;

        foreach (self::RUNS as $run) {
            for ($i = 0; $i < $run['count']; $i++) {
                $number = $run['from'] + $i;
                $section = $this->sectionFor($number);

                BazaarTable::updateOrCreate(
                    ['number' => $number],
                    [
                        'section' => $section,
                        'price' => self::TABLE_PRICE,
                        'pos_x' => $run['x'] + ($run['dx'] * $i),
                        'pos_y' => $run['y'] + ($run['dy'] * $i),
                        'width' => 29,
                        'height' => 22,
                        'rotation' => $run['rotation'] ?? 0,
                        'is_active' => true,
                        'is_bookable' => $section !== BazaarTable::SECTION_RESTAURANT,
                    ]
                );

                $count++;
            }
        }

        $this->command?->info("Bazaar tables seeded: {$count}");
    }

    /**
     * Section per table number, derived from the architect's legend
     * (A 14 · B 12 · C 56 · D 6 · Restaurants 12 = 100).
     */
    private function sectionFor(int $number): string
    {
        return match (true) {
            $number <= 12 => BazaarTable::SECTION_RESTAURANT,
            $number <= 18 => 'D',
            $number <= 22 => 'B',
            $number <= 47 => 'C',
            $number <= 53 => 'B',
            $number <= 77 => 'C',
            $number <= 83 => 'A',
            $number <= 85 => 'B',
            $number <= 89 => 'A',
            $number <= 96 => 'C',
            default => 'A',
        };
    }
}
