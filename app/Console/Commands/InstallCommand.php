<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'app:install {--fresh : Drop all tables before installing}';

    protected $description = 'Create the database schema, import the WorldSkills dump and prepare demo accounts';

    public function handle(): int
    {
        $this->prepareDirectories();
        $this->prepareSqliteDatabase();

        Artisan::call('migrate'.($this->option('fresh') ? ':fresh' : ''), ['--force' => true], $this->output);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DumpSeeder', '--force' => true], $this->output);

        $this->newLine();
        $this->info('Installation complete.');
        $this->line('Organizer login: demo1@worldskills.org / demopass1');
        $this->line('Attendee login:  Yakovich / 35DGZX');

        return self::SUCCESS;
    }

    /**
     * Make sure the writable Laravel directories exist on a fresh clone.
     */
    private function prepareDirectories(): void
    {
        $directories = [
            storage_path('app/private'),
            storage_path('app/public'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists($directory);
        }
    }

    /**
     * Create the SQLite file when the zero-configuration driver is selected.
     */
    private function prepareSqliteDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');

        if (! $database || $database === ':memory:') {
            return;
        }

        File::ensureDirectoryExists(dirname($database));

        if (! File::exists($database)) {
            File::put($database, '');
        }

        $this->line('SQLite database: '.$database);
    }
}
