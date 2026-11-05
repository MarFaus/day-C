<?php

namespace Database\Seeders;

use App\Models\Organizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Imports the original WorldSkills data dump (INSERT statements only).
 * The schema itself is created by the migrations, so the seeder works on
 * both MySQL/MariaDB and SQLite.
 */
class DumpSeeder extends Seeder
{
    private const TABLES = [
        'session_registrations',
        'registrations',
        'attendees',
        'event_tickets',
        'sessions',
        'rooms',
        'channels',
        'events',
        'organizers',
        'event_ratings',
        'session_ratings',
    ];

    public function run(): void
    {
        $path = database_path('db-dump.sql');

        if (! is_file($path)) {
            $this->command?->error('database/db-dump.sql not found.');

            return;
        }

        $this->truncate();

        $sql = file_get_contents($path);
        $imported = 0;

        foreach ($this->splitStatements($sql) as $statement) {
            if (! preg_match('/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]*)\)\s*VALUES\s*(.+)$/is', $statement, $match)) {
                continue;
            }

            $table = $match[1];
            $columns = array_map(
                fn ($column) => trim($column, " \t\n\r`"),
                explode(',', $match[2])
            );

            foreach (array_chunk($this->parseTuples($match[3]), 200) as $chunk) {
                $rows = [];
                foreach ($chunk as $tuple) {
                    $row = [];
                    foreach ($columns as $index => $column) {
                        $row[$column] = $tuple[$index] ?? null;
                    }
                    $rows[] = $row;
                }

                if ($rows !== []) {
                    DB::table($table)->insert($rows);
                    $imported += count($rows);
                }
            }
        }

        $this->setDemoPasswords();

        $this->command?->info("Imported {$imported} rows from db-dump.sql.");
    }

    private function truncate(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach (self::TABLES as $table) {
            DB::table($table)->delete();
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function setDemoPasswords(): void
    {
        $passwords = [
            'demo1@worldskills.org' => 'demopass1',
            'demo2@worldskills.org' => 'demopass2',
        ];

        foreach ($passwords as $email => $password) {
            Organizer::query()->where('email', $email)->update([
                'password_hash' => Hash::make($password),
            ]);
        }
    }

    /**
     * Split a MySQL dump into statements while ignoring semicolons in strings.
     *
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        // Remove whole-line comments before splitting. The original dump uses
        // "--" comment blocks between statements; without this step the comment
        // text becomes part of the next statement and the INSERT is skipped.
        $sql = preg_replace('/^\s*(?:--|#).*$/m', '', $sql) ?? $sql;
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;

        $statements = [];
        $current = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                $current .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $current .= $sql[++$i];
                } elseif ($char === $quote) {
                    if ($i + 1 < $length && $sql[$i + 1] === $quote) {
                        $current .= $sql[++$i];
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
                $current .= $char;
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '' && ! str_starts_with($trimmed, '--') && ! str_starts_with($trimmed, '#')) {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        return $statements;
    }

    /**
     * Parse "(1, 'foo', NULL), (2, 'bar', NULL)" into an array of tuples.
     *
     * @return array<int, array<int, mixed>>
     */
    private function parseTuples(string $values): array
    {
        $tuples = [];
        $tuple = null;
        $value = '';
        $quote = null;
        $length = strlen($values);

        for ($i = 0; $i < $length; $i++) {
            $char = $values[$i];

            if ($quote !== null) {
                if ($char === '\\' && $i + 1 < $length) {
                    $value .= $char.$values[++$i];
                } elseif ($char === $quote) {
                    if ($i + 1 < $length && $values[$i + 1] === $quote) {
                        $value .= $char.$values[++$i];
                    } else {
                        $quote = null;
                    }
                } else {
                    $value .= $char;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
                continue;
            }

            if ($char === '(') {
                $tuple = [];
                $value = '';
                continue;
            }

            if ($char === ',' && $tuple !== null) {
                $tuple[] = $this->castValue($value);
                $value = '';
                continue;
            }

            if ($char === ')' && $tuple !== null) {
                $tuple[] = $this->castValue($value);
                $tuples[] = $tuple;
                $tuple = null;
                $value = '';
                continue;
            }

            if ($tuple !== null) {
                $value .= $char;
            }
        }

        return $tuples;
    }

    private function castValue(string $value): mixed
    {
        $value = trim($value);

        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        return stripcslashes($value);
    }
}
