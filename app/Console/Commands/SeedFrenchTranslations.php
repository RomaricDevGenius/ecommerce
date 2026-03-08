<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Translation;
use Cache;

class SeedFrenchTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:seed-french';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed all French translations from the hardcoded fallback into the database, overwriting wrong English-seeded values.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $translations = get_french_translations_fallback();

        $bar = $this->output->createProgressBar(count($translations));
        $bar->start();

        $count = 0;
        foreach ($translations as $key => $value) {
            Translation::updateOrCreate(
                ['lang' => 'fr', 'lang_key' => $key],
                ['lang_value' => $value]
            );
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Clear the French translation cache
        Cache::forget('translations-fr');

        $this->info("✓ {$count} French translations seeded to the database.");
        $this->info("✓ French translation cache cleared.");
        $this->info("You can now use Google Auto-Translate from the admin panel to translate the remaining keys.");

        return Command::SUCCESS;
    }
}
