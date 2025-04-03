<div class="max-w-screen-xl mx-auto px-4 lg:px-12">
    <!-- Error Message -->
    @if ($errorMessage)
        <div class="text-red-500 mb-5 text-center">{{ $errorMessage }}</div>
    @endif

    <section class="bg-zinc-50 dark:bg-zinc-900 flex items-center py-4">
        <div class="w-full">
            <div class="relative bg-white shadow-md dark:bg-zinc-800 sm:rounded-lg drop-shadow-md">
                <div class="flex flex-col items-center justify-between p-4 space-y-3 md:flex-row md:space-y-0 md:space-x-4">
                    <div class="w-full">
                        <form class="flex items-center">
                            <label for="simple-search" class="sr-only">Search</label>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg aria-hidden="true" class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" id="search-term" wire:model.live.debounce.1000ms="searchTerm" placeholder="Enter company name..." 
                                    class="block w-full p-2 pl-10 text-sm text-zinc-900 border border-zinc-300 rounded-lg bg-zinc-50 focus:ring-red-500 focus:border-red-500 dark:bg-zinc-700 dark:border-zinc-600 dark:placeholder-zinc-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500" 
                                    required>
                            </div>
                        </form>
                    </div>
                    <div class="flex flex-col items-stretch justify-end flex-shrink-0 w-full space-y-2 md:w-auto md:flex-row md:space-y-0 md:items-center md:space-x-3">
                        <select wire:model.live="selected_year" class="border rounded p-2 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-red-500 focus:border-red-500">
                            @foreach ($years as $year)
                                <option value="{{ $year['value'] }}">{{ $year['label'] }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="selected_month" class="border rounded p-2 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white focus:ring-red-500 focus:border-red-500">
                            @foreach ($months as $month)
                                <option value="{{ $month['value'] }}">{{ $month['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Loading Spinner (Centered) -->
    <div wire:loading class="flex justify-center mt-6">
        <div role="status">
            <svg aria-hidden="true" class="inline w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-red-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
            </svg>
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <!-- Results -->
    <div wire:loading.remove class="mt-6">
        @if (count($companies) > 0 || count($callSignStats) > 0)
            <!-- Top Advertisers -->
            <h3 class="text-lg font-semibold mb-2 text-zinc-900 dark:text-white"> Advertising for {{ \DateTime::createFromFormat('ym', $selected_year . $selected_month)->format('F 20y') }}</h3>
            @if (count($companies) > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md drop-shadow-md">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="bg-red-100 dark:bg-red-900/20">
                            <tr>
                                <th class="p-3 font-medium text-zinc-900 dark:text-white">Name</th>
                                <th class="p-3 font-medium text-zinc-900 dark:text-white">Total Duration</th>
                                <th class="p-3 font-medium text-zinc-900 dark:text-white">Total Airings</th>
                                <th class="p-3 font-medium text-zinc-900 dark:text-white">Call Signs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $index => $company)
                                <tr class="{{ $index % 2 == 0 ? 'bg-gray-50 dark:bg-zinc-900' : 'bg-white dark:bg-zinc-800' }} hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
                                    <td class="p-3">{{ $company['name'] }}</td>
                                    <td class="p-3">{{ $this->formatDuration($company['total_duration']) }}</td>
                                    <td class="p-3">{{ $company['total_airings'] }}</td>
                                    <td class="p-3">
                                        @if (!empty($company['call_signs']))
                                            <ul class="list-disc pl-4 text-xs">
                                                @foreach ($company['call_signs'] as $callSign)
                                                    <li>{{ $callSign['call_sign'] }}: {{ $callSign['airings'] }} airings, {{ $this->formatDuration($callSign['duration']) }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">No call sign data</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center">No advertisers found for this month with the given search term.</p>
            @endif

            <!-- Call Sign Breakdown -->
            <h3 class="text-lg font-semibold mb-2 mt-6 text-zinc-900 dark:text-white">Call Sign Breakdown for {{ \DateTime::createFromFormat('ym', $selected_year . $selected_month)->format('F 20y') }}</h3>
            @if (count($callSignStats) > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md drop-shadow-md">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="bg-red-100 dark:bg-red-900/20">
                            <tr>
                                <th class="p-3 font-medium text-zinc-900 dark:text-white">Call Sign</th>
                                <th class="p-3 font-medium text-zinc-900 dark:text-white">Total Airtime</th>
                                <th class="p-3 font-medium text-zinc-900 dark:text-white">Airings</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($callSignStats as $index => $stat)
                                <tr class="{{ $index % 2 == 0 ? 'bg-gray-50 dark:bg-zinc-900' : 'bg-white dark:bg-zinc-800' }} hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
                                    <td class="p-3">{{ $stat['call_sign'] }}</td>
                                    <td class="p-3">{{ $this->formatDuration($stat['total_airtime']) }}</td>
                                    <td class="p-3">{{ $stat['airings'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center">No call sign data for this month.</p>
            @endif
        @else
            <p class="text-gray-500 dark:text-gray-400 text-center mt-4">Enter a search term to see results.</p>
        @endif
    </div>
</div>