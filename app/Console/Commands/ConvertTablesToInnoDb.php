<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertTablesToInnoDb extends Command
{
    protected $signature = 'db:convert-to-innodb
        {tables?* : Noms de tables spécifiques (sinon toutes les tables MyISAM restantes)}
        {--dry-run : Afficher seulement la liste sans convertir}';

    protected $description = 'Convertit des tables MyISAM en InnoDB avec vérification du nombre de lignes avant/après';

    public function handle(): int
    {
        $requested = $this->argument('tables');

        $query = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('ENGINE', 'MyISAM');

        if (! empty($requested)) {
            $query->whereIn('TABLE_NAME', $requested);
        }

        $tables = $query->orderBy('TABLE_NAME')->pluck('TABLE_NAME');

        if ($tables->isEmpty()) {
            $this->info('Aucune table MyISAM à convertir (déjà toutes en InnoDB, ou noms invalides).');
            return self::SUCCESS;
        }

        $this->info('Tables à convertir : ' . $tables->implode(', '));

        if ($this->option('dry-run')) {
            $this->comment('Dry-run actif : aucune conversion effectuée.');
            return self::SUCCESS;
        }

        $results = [];

        foreach ($tables as $table) {
            $before = (int) DB::table($table)->count();

            try {
                DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            } catch (\Throwable $e) {
                $results[] = [$table, $before, '—', 'ÉCHEC : ' . $e->getMessage()];
                continue;
            }

            $after = (int) DB::table($table)->count();
            $status = $after === $before ? 'OK' : 'ATTENTION : nombre de lignes différent !';

            $results[] = [$table, $before, $after, $status];
        }

        $this->newLine();
        $this->table(['Table', 'Lignes avant', 'Lignes après', 'Statut'], $results);

        $failures = collect($results)->filter(fn ($r) => str_contains($r[3], 'ÉCHEC') || str_contains($r[3], 'ATTENTION'));

        if ($failures->isNotEmpty()) {
            $this->error($failures->count() . ' table(s) en erreur ou à vérifier manuellement.');
            return self::FAILURE;
        }

        $this->info('Toutes les tables converties avec succès, aucune perte de lignes détectée.');
        return self::SUCCESS;
    }
}
