<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\TvLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AdvertisterTable extends Component {
    public $companies = [];
    public $callSignStats = [];
    public $years = [];
    public $months = [];
    public $selected_year = '25'; // Default to 2025
    public $selected_month = '01'; // Default to January
    public $searchTerm = '';
    public $errorMessage = '';

    public function mount() {
        try {
            // Get unique years from the database
            $this->years = TvLog::selectRaw("DISTINCT SUBSTR(log_date, 1, 2) AS year")
                ->orderBy('year')
                ->pluck('year')
                ->map(function ($year) {
                    return [
                        'value' => $year,
                        'label' => '20' . $year,
                    ];
                })->all();

            // Get unique months for the selected year
            $this->updateMonths();

            // Load stats for the default selection
            $this->loadStats();
        } catch (\Exception $e) {
            Log::error("Error in AdvertisterTable mount: " . $e->getMessage());
            $this->errorMessage = "An error occurred while loading data. Please try again.";
        }
    }

    public function updatedSelectedYear() {
        $this->updateMonths();
        $this->loadStats();
    }

    public function updatedSelectedMonth() {
        $this->loadStats();
    }

    public function updatedSearchTerm() {
        $this->loadStats();
    }

    private function updateMonths() {
        try {
            $this->months = TvLog::selectRaw("DISTINCT SUBSTR(log_date, 3, 2) AS month")
                ->where('log_date', 'LIKE', $this->selected_year . '%')
                ->orderBy('month')
                ->pluck('month')
                ->map(function ($month) {
                    return [
                        'value' => $month,
                        'label' => \DateTime::createFromFormat('m', $month)->format('F'),
                    ];
                })->all();

            // Reset selected month if it's not in the new list
            if (!in_array($this->selected_month, array_column($this->months, 'value'))) {
                $this->selected_month = $this->months[0]['value'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error("Error updating months: " . $e->getMessage());
            $this->errorMessage = "An error occurred while updating months.";
        }
    }

    public function loadStats() {
        $this->errorMessage = '';
        $this->companies = [];
        $this->callSignStats = [];

        if (!$this->selected_year || !$this->selected_month) {
            return;
        }

        $logDatePrefix = $this->selected_year . $this->selected_month;

        try {
            // Query for advertisers with call sign breakdown
            $companyQuery = TvLog::query()
                ->select(
                    'program_title',
                    'call_sign',
                    TvLog::raw('SUM(CAST(duration AS INTEGER)) as duration'),
                    TvLog::raw('COUNT(*) as airings')
                )
                ->whereIn('program_class', ['COM', 'PSA'])
                ->where('log_date', 'LIKE', "$logDatePrefix%");

            if ($this->searchTerm) {
                $companyQuery->where('program_title', 'LIKE', '%' . $this->searchTerm . '%');
            }

            // Group by program_title and call_sign to get per-call-sign stats
            $rawData = TvLog::query()
                ->select('program_title')
                ->whereIn('program_class', ['COM', 'PSA'])
                ->where('log_date', 'LIKE', "$logDatePrefix%")
                ->groupBy('program_title')
                ->get();

            $this->companies = $rawData->map(function ($item) {
                return [
                    'name' => preg_replace("/[^A-Za-z0-9 ]/", '', $item->program_title),
                    'total_duration' => 0,
                    'total_airings' => 0,
                    'call_signs' => [],
                    'slug' => Str::slug($item->program_title, '-'),
                ];
            })->take(10)->values()->all();

            // Query for call sign stats (airings and total airtime)
            $this->callSignStats = TvLog::query()
                ->select(
                    'call_sign',
                    TvLog::raw('SUM(CAST(duration AS INTEGER)) as total_airtime'),
                    TvLog::raw('COUNT(*) as airings')
                )
                ->whereIn('program_class', ['COM', 'PSA'])
                ->where('log_date', 'LIKE', "$logDatePrefix%")
                ->groupBy('call_sign')
                ->orderByDesc('total_airtime')
                ->get()
                ->map(function ($item) {
                    return [
                        'call_sign' => $item->call_sign,
                        'total_airtime' => $item->total_airtime,
                        'airings' => $item->airings,
                    ];
                })->all();
        } catch (\Exception $e) {
            Log::error("Error loading stats: " . $e->getMessage());
            $this->errorMessage = "An error occurred while loading stats. Please try again.";
        }
    }

    public function render() {
        return view('livewire.advertister-table')->layout('layouts.app');
    }
}