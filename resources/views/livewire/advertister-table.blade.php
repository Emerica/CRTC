<div>
    <h1>CRTC TV Logs</h1>

    <!-- Error Message -->
    @if ($errorMessage)
        <div style="color: red; margin-bottom: 20px;">
            {{ $errorMessage }}
        </div>
    @endif

    <!-- Year and Month Selectors -->
    <div style="margin-bottom: 20px;">
        <label for="year-select">Select Year:</label>
        <select id="year-select" wire:model.live="selected_year">
            @foreach ($years as $year)
                <option value="{{ $year['value'] }}">{{ $year['label'] }}</option>
            @endforeach
        </select>

        <label for="month-select" style="margin-left: 20px;">Select Month:</label>
        <select id="month-select" wire:model.live="selected_month">
            @foreach ($months as $month)
                <option value="{{ $month['value'] }}">{{ $month['label'] }}</option>
            @endforeach
        </select>
    </div>

    <!-- Search Input -->
    <div style="margin-bottom: 20px;">
        <label for="search-term">Search Company Name:</label>
        <input type="text" id="search-term" wire:model.debounce.500ms="searchTerm" placeholder="Enter company name..." style="padding: 5px; width: 300px;" />
    </div>

    <!-- Top Advertisers with Call Sign Breakdown -->
    <h3>Top Advertisers for {{ \DateTime::createFromFormat('ym', $selected_year . $selected_month)->format('F 20y') }}</h3>
    @if (count($companies) > 0)
        @foreach ($companies as $company)
            <div style="margin-bottom: 20px;">
                <h4>{{ $company['name'] }} (Total: {{ $company['total_duration'] }} seconds, {{ $company['total_airings'] }} airings)</h4>
                @if (!empty($company['call_signs']))
                    <table style="width: 100%; border-collapse: collapse; margin-left: 20px;">
                        <thead>
                            <tr style="background-color: #f2f2f2;">
                                <th style="padding: 8px; border: 1px solid #ddd;">Call Sign</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Airings</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Duration (seconds)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($company['call_signs'] as $callSign)
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $callSign['call_sign'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $callSign['airings'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $callSign['duration'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No call sign data available for this company.</p>
                @endif
            </div>
        @endforeach
    @else
        <p>No advertisers found for this month with the given search term.</p>
    @endif

    <!-- Call Sign Breakdown Table -->
    <h3>Call Sign Breakdown for {{ \DateTime::createFromFormat('ym', $selected_year . $selected_month)->format('F 20y') }}</h3>
    @if (count($callSignStats) > 0)
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="padding: 8px; border: 1px solid #ddd;">Call Sign</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Total Airtime (seconds)</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Airings</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($callSignStats as $stat)
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $stat['call_sign'] }}</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $stat['total_airtime'] }}</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $stat['airings'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No call sign data for this month.</p>
    @endif
</div>