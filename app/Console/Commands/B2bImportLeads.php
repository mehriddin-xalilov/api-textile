<?php

namespace App\Console\Commands;

use App\Models\B2bLead;
use Illuminate\Console\Command;

/**
 * Mailer skripti yozgan leads.csv ni bazaga yuklaydi (token,company,email,phone,segment,sent_at).
 * Qayta ishga tushirilsa mavjud yozuvlar yangilanadi, statistika o'chmaydi.
 */
class B2bImportLeads extends Command
{
    protected $signature = 'b2b:import {file : leads.csv yo\'li} {--campaign=b2b}';

    protected $description = 'B2B kampaniya lidlarini CSV dan yuklash';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $this->error("Fayl topilmadi: {$path}");

            return self::FAILURE;
        }

        $fh = fopen($path, 'r');
        $header = null;
        $n = 0;
        while (($row = fgetcsv($fh)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h, "\xEF\xBB\xBF \t")), $row);

                continue;
            }
            $d = array_combine($header, array_pad($row, count($header), null));
            if (empty($d['email'])) {
                continue;
            }
            $email = strtolower(trim($d['email']));
            B2bLead::query()->updateOrCreate(
                ['token' => $d['token'] ?: B2bLead::tokenFor($email)],
                [
                    'company' => $d['company'] ?? '',
                    'email' => $email,
                    'phone' => $d['phone'] ?? null,
                    'segment' => $d['segment'] ?? null,
                    'campaign' => (string) $this->option('campaign'),
                    'sent_at' => ! empty($d['sent_at']) ? $d['sent_at'] : null,
                ],
            );
            $n++;
        }
        fclose($fh);
        $this->info("{$n} ta lid yuklandi.");

        return self::SUCCESS;
    }
}
