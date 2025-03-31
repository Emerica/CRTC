<?php
namespace App\Livewire;

use Log;
use Http;
use ZipArchive;
use Livewire\Component;
use SQLite3;

class LogSelect extends Component {
    public $id = "log-select";
    public $base_remote_path = "https://applications.crtc.gc.ca/OpenData/Television%20Logs/STAR2/";
    public $file_list = [];
    public $selected_file;

    public function mount() {
        #$this->scanLogs();
    }

    public function render() {
        $this->file_list = $this->loadFiles();
        return view('livewire.log-select');
    }

    public function scanLogs() {
        $startDate = \DateTime::createFromFormat('Y-m-d', '2023-03-01');
        $endDate = \DateTime::createFromFormat('Y-m-d', '2025-02-28');
        $interval = new \DateInterval('P1M');

        $current = clone $startDate;
        while ($current <= $endDate) {
            $year = $current->format('Y');
            $month = $current->format('m');
            $this->getZip($year, $month);
            $current->add($interval);
        }
    }

    public function getZip($year, $month) {
        $year = intval($year);
        $month = str_pad(intval($month), 2, "0", STR_PAD_LEFT);
        $zipFile = "$year-$month.zip";
        $path = $this->base_remote_path . $year . "/" . $zipFile;

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
        }
    }

    public function importFiles($year, $month) {
        $db = new SQLite3(database_path('database.sqlite'));
        if (!$db) {
            Log::error("Failed to connect to SQLite database");
            return;
        }

        $dir = storage_path("tvlogs/$year-$month");
        $files = glob("$dir/*.log");

        Log::info("Importing $year, $month");

        $db->exec('BEGIN TRANSACTION');
        $stmt = $db->prepare(
            'INSERT INTO tv_logs (
                log_format, program_class, affiliation_type, call_sign, log_date,
                start_time, end_time, duration, program_title, program_sub_title,
                producer1, producer2, production_number, special_attention, origin,
                timecredits, exhibition, production_source, target_audience, categories,
                accessible_programming, dubbing_credit, ethnic_program, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )'
        );

        foreach ($files as $file) {
            Log::info("Importing $file");
            $handle = fopen($file, 'r');
            $batchCount = 0;
            $batchSize = 100;

            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;

                $dat = $this->parseLine($line);

                $stmt->bindValue(1, $dat['logFormat'], SQLITE3_TEXT);
                $stmt->bindValue(2, $dat['programClass'], SQLITE3_TEXT);
                $stmt->bindValue(3, $dat['affiliationType'], SQLITE3_TEXT);
                $stmt->bindValue(4, $dat['callSign'], SQLITE3_TEXT);
                $stmt->bindValue(5, $dat['logDate'], SQLITE3_TEXT);
                $stmt->bindValue(6, $dat['startTime'], SQLITE3_TEXT);
                $stmt->bindValue(7, $dat['endTime'], SQLITE3_TEXT);
                $stmt->bindValue(8, $dat['duration'], SQLITE3_TEXT);
                $stmt->bindValue(9, $dat['programTitle'], SQLITE3_TEXT);
                $stmt->bindValue(10, $dat['programSubTitle'], SQLITE3_TEXT);
                $stmt->bindValue(11, $dat['producer1'], SQLITE3_TEXT);
                $stmt->bindValue(12, $dat['producer2'], SQLITE3_TEXT);
                $stmt->bindValue(13, $dat['productionNumber'], SQLITE3_TEXT);
                $stmt->bindValue(14, $dat['specialAttention'], SQLITE3_TEXT);
                $stmt->bindValue(15, $dat['origin'], SQLITE3_TEXT);
                $stmt->bindValue(16, $dat['timecredits'], SQLITE3_TEXT);
                $stmt->bindValue(17, $dat['exhibition'], SQLITE3_TEXT);
                $stmt->bindValue(18, $dat['productionSource'], SQLITE3_TEXT);
                $stmt->bindValue(19, $dat['targetAudience'], SQLITE3_TEXT);
                $stmt->bindValue(20, $dat['categories'], SQLITE3_TEXT);
                $stmt->bindValue(21, $dat['accessibleProgramming'], SQLITE3_TEXT);
                $stmt->bindValue(22, $dat['dubbingCredit'], SQLITE3_TEXT);
                $stmt->bindValue(23, $dat['ethnicProgram'], SQLITE3_TEXT);
                $stmt->bindValue(24, now()->toDateTimeString(), SQLITE3_TEXT);
                $stmt->bindValue(25, now()->toDateTimeString(), SQLITE3_TEXT);

                $stmt->execute();
                $stmt->reset();
                $batchCount++;

                if ($batchCount >= $batchSize) {
                    $db->exec('COMMIT');
                    $db->exec('BEGIN TRANSACTION');
                    $batchCount = 0;
                    #Log::info("Peak memory: " . memory_get_peak_usage() / 1024 / 1024 . " MB");
                }
            }

            fclose($handle);
        }

        if ($batchCount > 0) {
            $db->exec('COMMIT');
        } else {
            $db->exec('ROLLBACK');
        }

        $db->close();
        $this->dispatch('fileImported', "$year-$month");
    }

    public function parseLine($line) {
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

    public function loadFiles() {
        $path = storage_path('tvlogs');
        $glob = glob("$path/*/*");
        return $glob;
    }

    public function fileSelected() {
        $this->dispatch('fileSelected', $this->selected_file);
    }
}