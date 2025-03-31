<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use ZipArchive;
use App\Models\TvLog;
use Log;

class ImportTvLogs extends Command {
    protected $signature = 'import:tvlogs';
    protected $description = 'Import CRTC TV logs into SQLite';

    private $baseRemotePath = "https://applications.crtc.gc.ca/OpenData/Television%20Logs/STAR2/";

    public function handle() {
        $startDate = \DateTime::createFromFormat('Y-m-d', '2025-02-01');
        $endDate = \DateTime::createFromFormat('Y-m-d', '2025-03-28');
        $interval = new \DateInterval('P1M');

        $current = clone $startDate;
        while ($current <= $endDate) {
            $year = $current->format('Y');
            $month = $current->format('m');
            $this->getZip($year, $month);
            $current->add($interval);
        }

        $this->info("Import completed.");
    }

    private function getZip($year, $month) {
        $year = intval($year);
        $month = str_pad(intval($month), 2, "0", STR_PAD_LEFT);
        $zipFile = "$year-$month.zip";
        $path = $this->baseRemotePath . $year . "/" . $zipFile;

        if (file_exists(storage_path($zipFile))) {
            $this->importFiles($year, $month);
            return;
        }

        Http::sink(storage_path($zipFile))->get($path);
        $zipArchive = new ZipArchive();
        $result = $zipArchive->open(storage_path($zipFile));

        if ($result === TRUE) {
            $zipArchive->extractTo(storage_path("tvlogs/$year-$month"));
            $zipArchive->close();
            $this->importFiles($year, $month);
            unlink(storage_path($zipFile));
        } else {
            Log::error("Failed to unzip $zipFile");
            $this->error("Failed to unzip $zipFile");
        }
    }

    private function importFiles($year, $month) {
        $dir = storage_path("tvlogs/$year-$month");
        $files = glob("$dir/*.log");

        $this->info("Importing $year-$month");

        foreach ($files as $file) {
            $this->info("Importing $file");
            $handle = fopen($file, 'r');
            $batch = [];
            $batchSize = 100;

            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;

                $dat = $this->parseLine($line);

                $batch[] = [
                    'log_format' => $dat['logFormat'],
                    'program_class' => $dat['programClass'],
                    'affiliation_type' => $dat['affiliationType'],
                    'call_sign' => $dat['callSign'],
                    'log_date' => $dat['logDate'],
                    'start_time' => $dat['startTime'],
                    'end_time' => $dat['endTime'],
                    'duration' => $dat['duration'],
                    'program_title' => $dat['programTitle'],
                    'program_sub_title' => $dat['programSubTitle'],
                    'producer1' => $dat['producer1'],
                    'producer2' => $dat['producer2'],
                    'production_number' => $dat['productionNumber'],
                    'special_attention' => $dat['specialAttention'],
                    'origin' => $dat['origin'],
                    'timecredits' => $dat['timecredits'],
                    'exhibition' => $dat['exhibition'],
                    'production_source' => $dat['productionSource'],
                    'target_audience' => $dat['targetAudience'],
                    'categories' => $dat['categories'],
                    'accessible_programming' => $dat['accessibleProgramming'],
                    'dubbing_credit' => $dat['dubbingCredit'],
                    'ethnic_program' => $dat['ethnicProgram'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $batchSize) {
                    TvLog::insert($batch);
                    $batch = [];
                    #$this->info("Peak memory: " . memory_get_peak_usage() / 1024 / 1024 . " MB");
                }
            }

            if (!empty($batch)) {
                TvLog::insert($batch);
            }

            fclose($handle);
        }
    }

    private function parseLine($line) {
        $fields = [
            'logFormat' => 1, 'programClass' => 3, 'affiliationType' => 2,
            'callSign' => 6, 'logDate' => 6, 'startTime' => 6, 'endTime' => 6,
            'duration' => 6, 'programTitle' => 50, 'programSubTitle' => 50,
            'producer1' => 6, 'producer2' => 6, 'productionNumber' => 6,
            'specialAttention' => 1, 'origin' => 1, 'timecredits' => 1,
            'exhibition' => 1, 'productionSource' => 1, 'targetAudience' => 1,
            'categories' => 3, 'accessibleProgramming' => 2, 'dubbingCredit' => 1,
            'ethnicProgram' => 1
        ];
        $parsedData = [];
        $offset = 0;
        foreach ($fields as $key => $length) {
            $parsedData[$key] = trim(substr($line, $offset, $length));
            $offset += $length;
        }
        return $parsedData;
    }
}