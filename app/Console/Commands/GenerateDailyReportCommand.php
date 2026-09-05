<?php

namespace App\Console\Commands;

use App\Services\DailyMarkdownReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDailyReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate-daily {date? : Business date in YYYY-MM-DD format} {--force : Force regenerate existing snapshot file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a deterministic daily markdown operational snapshot';

    public function handle(DailyMarkdownReportService $service): int
    {
        $dateInput = $this->argument('date');
        $force = (bool) $this->option('force');

        if ($dateInput) {
            try {
                $day = Carbon::parse($dateInput, 'Asia/Jakarta')->startOfDay();
            } catch (\Throwable $e) {
                $this->error("Invalid date format: {$dateInput}. Please use YYYY-MM-DD format.");
                return Command::FAILURE;
            }
        } else {
            $day = Carbon::now('Asia/Jakarta')->startOfDay();
        }

        $dateStr = $day->toDateString();
        $this->info("Generating Daily Markdown Snapshot for: {$dateStr} (Timezone: Asia/Jakarta)...");

        $result = $service->generate($day, $force);

        if ($result['status'] === 'exists') {
            $this->warn($result['message']);
            $this->line("File path: {$result['file_path']}");
            return Command::SUCCESS;
        }

        $this->info("✓ Snapshot successfully generated!");
        $this->table(
            ['Key', 'Value'],
            [
                ['Report Date', $result['date']],
                ['Snapshot Mode', $result['mode']],
                ['Status', strtoupper($result['status'])],
                ['Output File', $result['file_path']],
            ]
        );

        return Command::SUCCESS;
    }
}
