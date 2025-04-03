<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TvLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AdvertisterTable extends Component
{
    use WithPagination;

    public $companies = [];
    public $callSignStats = [];
    public $years = [];
    public $months = [];
    public $selected_year = '25'; // Default to 2025
    public $selected_month = '01'; // Default to January
    public $searchTerm = '';
    public $errorMessage = '';
    public $perPage = 10; // Pagination limit

    public function mount()
    {
        try {
            $this->years = TvLog::selectRaw("DISTINCT SUBSTR(log_date, 1, 2) AS year")
                ->orderBy('year')
                ->pluck('year')
                ->map(fn($year) => ['value' => $year, 'label' => '20' . $year])
                ->all();

            $this->updateMonths();
            $this->loadStats();
        } catch (\Exception $e) {
            Log::error("Error in AdvertisterTable mount: " . $e->getMessage());
            $this->errorMessage = "An error occurred while loading data.";
        }
    }

    public function updatedSelectedYear()
    {
        $this->updateMonths();
        $this->loadStats();
    }

    public function updatedSelectedMonth()
    {
        $this->loadStats();
    }

    public function updatedSearchTerm()
    {
        $this->loadStats();
    }

    private function updateMonths()
    {
        try {
            $this->months = TvLog::selectRaw("DISTINCT SUBSTR(log_date, 3, 2) AS month")
                ->where('log_date', 'LIKE', $this->selected_year . '%')
                ->orderBy('month')
                ->pluck('month')
                ->map(fn($month) => [
                    'value' => $month,
                    'label' => \DateTime::createFromFormat('m', $month)->format('F'),
                ])
                ->all();

            if (!in_array($this->selected_month, array_column($this->months, 'value'))) {
                $this->selected_month = $this->months[0]['value'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error("Error updating months: " . $e->getMessage());
            $this->errorMessage = "An error occurred while updating months.";
        }
    }

    public function loadStats()
    {
        $this->errorMessage = '';
        $this->companies = [];
        $this->callSignStats = [];

        if (!$this->selected_year || !$this->selected_month || !$this->searchTerm) {
            return; // Only load stats if all filters are set
        }

        $logDatePrefix = $this->selected_year . $this->selected_month;

        try {
            // Base query for companies with call sign breakdown
            $companyQuery = TvLog::whereIn('program_class', ['COM', 'PSA'])
                ->where('log_date', 'LIKE', "$logDatePrefix%")
                ->where('program_title', 'LIKE', '%' . $this->searchTerm . '%');

            // Get companies with aggregated stats
            $rawCompanies = $companyQuery->selectRaw('program_title, SUM(CAST(duration AS INTEGER)) as total_duration, COUNT(*) as total_airings')
                ->groupBy('program_title')
                ->orderBy('total_airings', 'desc') // Sort by total airings descending
                ->get();

            // Populate companies with call sign details
            $this->companies = $rawCompanies->map(function ($company) use ($logDatePrefix) {
                $callSigns = TvLog::where('program_title', $company->program_title)
                    ->whereIn('program_class', ['COM', 'PSA'])
                    ->where('log_date', 'LIKE', "$logDatePrefix%")
                    ->selectRaw('call_sign, SUM(CAST(duration AS INTEGER)) as duration, COUNT(*) as airings')
                    ->groupBy('call_sign')
                    ->orderBy('airings', 'desc') // Sort call signs by airings descending
                    ->get()
                    ->map(fn($item) => [
                        'call_sign' => $item->call_sign,
                        'duration' => $item->duration,
                        'airings' => $item->airings,
                    ])
                    ->all();

                return [
                    'name' => preg_replace("/[^A-Za-z0-9 ]/", '', $company->program_title),
                    'total_duration' => $company->total_duration ?? 0,
                    'total_airings' => $company->total_airings ?? 0,
                    'call_signs' => $callSigns,
                    'slug' => Str::slug($company->program_title, '-'),
                ];
            })->take(10)->values()->all();

            // Call sign stats (overall)
            $this->callSignStats = TvLog::whereIn('program_class', ['COM', 'PSA'])
                ->where('log_date', 'LIKE', "$logDatePrefix%")
                ->where('program_title', 'LIKE', '%' . $this->searchTerm . '%')
                ->selectRaw('call_sign, SUM(CAST(duration AS INTEGER)) as total_airtime, COUNT(*) as airings')
                ->groupBy('call_sign')
                ->orderBy('total_airtime', 'desc') // Sort by total airtime descending
                ->get()
                ->map(fn($item) => [
                    'call_sign' => $item->call_sign,
                    'total_airtime' => $item->total_airtime,
                    'airings' => $item->airings,
                ])
                ->all();

        } catch (\Exception $e) {
            Log::error("Error loading stats: " . $e->getMessage());
            $this->errorMessage = "An error occurred while loading stats.";
        }
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function render()
    {
        return view('livewire.advertister-table')->layout('layouts.app');
    }
}