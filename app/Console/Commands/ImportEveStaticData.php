<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ImportEveStaticData extends Command
{
    protected $signature = 'eve:import-static-data
                            {--table= : Import only a specific table}';

    // SDE conversions provided by Fuzzwork Enterprises: https://www.fuzzwork.co.uk/dump/
    protected $description = 'Import Fuzzwork SDE conversion SQL dumps into the database';

    private const TABLES = [
        'mapRegions',
        'mapSolarSystems',
        'invTypes',
        'invTypeMaterials',
        'invUniqueNames',
    ];

    public function handle(): int
    {
        $tableOption = $this->option('table');

        if ($tableOption !== null) {
            if (!in_array($tableOption, self::TABLES, true)) {
                $this->error("Unknown table: {$tableOption}");
                $this->line('Valid tables: ' . implode(', ', self::TABLES));
                return self::FAILURE;
            }
            $tables = [$tableOption];
        } else {
            $tables = self::TABLES;
        }

        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        foreach ($tables as $table) {
            $sqlFile = database_path("eve-static/{$table}.sql");

            if (!file_exists($sqlFile)) {
                $this->warn("Skipping {$table}: file not found at {$sqlFile}");
                continue;
            }

            $this->info("Importing {$table}...");

            $command = [
                'mysql',
                '--host=' . $host,
                '--port=' . $port,
                '--user=' . $username,
                $database,
            ];

            $process = new Process($command);
            $process->setInput(fopen($sqlFile, 'r'));
            $process->setTimeout(300);
            $process->setEnv(['MYSQL_PWD' => $password]);

            $process->run();

            if (!$process->isSuccessful()) {
                $this->error("Failed to import {$table}:");
                $this->error($process->getErrorOutput());
                return self::FAILURE;
            }

            $this->info("  {$table} imported successfully.");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
