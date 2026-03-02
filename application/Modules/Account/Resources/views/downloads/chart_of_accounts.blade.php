<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHART OF ACCOUNTS</title>
    <style>
        body {
            /*font-family: "Times New Roman", sans-serif;*/
            font-size: 12px;
            line-height: 0.9; /* Adjust line spacing */
            padding: 0 !important;
            margin: 0 !important;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .company-info {
            text-align: center;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 0.9 !important;
        }
        .logistics {
            margin-top: 10px;
            font-weight: bold;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            z-index: -1;
        }

        .heading {
            color: green;
            font-size: 13px !important;
            font-weight: bold !important;
            margin: 5px !important;
            padding: 0 !important;
        }
        .tfooter {
            font-weight: bold !important;
        }

        .footer {
            font-size: 10px;
            text-align: center;
            position: fixed;
            bottom: 10px;
            width: 100%;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .footer-content .left {
            text-align: left;
            width: 33%;
        }

        .footer-content .center {
            text-align: center;
            width: 33%;
        }

        .footer-content .right {
            text-align: right;
            width: 33%;
        }

        .logo {
            height: 50px !important;
            width: 50px !important;
            padding-bottom: 2px !important;
        }
        .dotted-hr {
            margin-bottom: 0 !important;
            padding: 0 !important;
        }
        .underline {
            display: inline-block;
            border-bottom: 1px solid black; /* Adjust thickness & color */
            padding-bottom: 3px; /* Controls space between text & underline */
        }


    </style>
</head>
<body>

<div class="company-info">
    <img class="logo" src="{{ asset('assets/img/favicons/icon.png') }}" alt="Logo">
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
    <p>PIN NO: P051685374V</p>
</div>

<div class="header">CHART OF ACCOUNTS <hr></div>

@php
    // First, group accounts hierarchically
    $groupedAccounts = [];
    foreach ($accounts as $account) {
        $master = $account['account_name'];
        $group = $account['sub_account_name'];
        $subgroup = $account['chart_name'];

        if (!isset($groupedAccounts[$master])) {
            $groupedAccounts[$master] = [];
        }
        if (!isset($groupedAccounts[$master][$group])) {
            $groupedAccounts[$master][$group] = [];
        }
        $groupedAccounts[$master][$group][$subgroup][] = $account;
    }

    // Calculate row counts for each level
    $rowCounts = [];
    foreach ($groupedAccounts as $master => $groups) {
        $masterCount = 0;
        foreach ($groups as $group => $subgroups) {
            $groupCount = 0;
            foreach ($subgroups as $subgroup => $accounts) {
                $count = count($accounts);
                $rowCounts[$master][$group][$subgroup] = $count;
                $groupCount += $count;
                $masterCount += $count;
            }
            $rowCounts[$master][$group]['total'] = $groupCount;
        }
        $rowCounts[$master]['total'] = $masterCount;
    }

    $rowsPerPage = 27; // Adjust based on your content height
    $currentPageRows = 0;
    $accSn = 0;
@endphp

<table style="width: 100%; border-collapse: collapse; font-family: Cambria,sans-serif; font-size: 8pt; page-break-inside: avoid;">
    <thead>
    <tr>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 3% !important;">#</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 10% !important;">Master Ledger</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 10% !important;">Group Ledger</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 15% !important;">Sub Group</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 10% !important;">Acc Number</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 25% !important;">Account Name</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 10% !important;">Currency</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 10% !important;">Status</th>
        <th style="border: 1px solid #000; padding: 4px; text-align: left; font-family: 'Book Antiqua',sans-serif; font-size: 10pt; font-weight: bold; width: 10% !important;">Created By</th>
    </tr>
    </thead>
    @php
        // Initialize counter at 0 before the loop
        $accSn = 0;
    @endphp

    <tbody>
    @foreach($groupedAccounts as $masterName => $groups)
        @php
            $masterRowspan = $rowCounts[$masterName]['total'];
            $masterRowsRemaining = $masterRowspan;
            $masterFirst = true;
            $masterContinued = false;

            // Increment counter ONLY when starting a new master ledger
            $currentMasterNumber = ++$accSn;
        @endphp

        @foreach($groups as $groupName => $subGroups)
            @php
                $groupRowspan = $rowCounts[$masterName][$groupName]['total'];
                $groupRowsRemaining = $groupRowspan;
                $groupFirst = true;
                $groupContinued = false;
            @endphp

            @foreach($subGroups as $subGroupName => $accounts)
                @php
                    $subGroupRowspan = count($accounts);
                    $subGroupRowsRemaining = $subGroupRowspan;
                    $subGroupFirst = true;
                @endphp

                @foreach($accounts as $account)
                    @php
                        if ($currentPageRows >= $rowsPerPage) {
                            echo '</tbody></table>';
                            echo '<div style="page-break-before: always;"></div>';
                            echo '<table style="width: 100%; border-collapse: collapse; font-family: Cambria; font-size: 8pt;">';
                            echo '<thead><tr>';
                            echo '<th style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle; font-family: \'Book Antiqua\'; font-size: 11pt; font-weight: bold;">#</th>';
                            // Repeat all headers...
                            echo '</tr></thead><tbody>';
                            $currentPageRows = 0;
                            $masterFirst = true;
                            $groupFirst = true;
                            $subGroupFirst = true;
                        }
                        $currentPageRows++;
                    @endphp

                    <tr>
                        @if($masterFirst)
                            @php
                                $rowsToSpan = min($masterRowsRemaining, $rowsPerPage - $currentPageRows + 1);
                                if ($rowsToSpan < 1) $rowsToSpan = 1;
                            @endphp
                            <td rowspan="{{ $rowsToSpan }}" style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle; display: table-cell; width: 10% !important;">
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                    {{ $currentMasterNumber }} <!-- Use the pre-calculated master number -->
                                </div>
                            </td>
                            <td rowspan="{{ $rowsToSpan }}" style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle; display: table-cell; width: 10% !important;">
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                    {{ strtoupper($masterName) }}
                                    @if($masterContinued) (cont.) @endif
                                </div>
                            </td>
                            @php $masterFirst = false; @endphp
                        @endif

                        <!-- Rest of your row code remains the same -->
                        @if($groupFirst)
                            @php
                                $rowsToSpan = min($groupRowsRemaining, $rowsPerPage - $currentPageRows + 1);
                                if ($rowsToSpan < 1) $rowsToSpan = 1;
                            @endphp
                            <td rowspan="{{ $rowsToSpan }}" style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle; display: table-cell; width: 10% !important;">
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                    {{ strtoupper($groupName) }}
                                    @if($groupContinued) (cont.) @endif
                                </div>
                            </td>
                            @php $groupFirst = false; @endphp
                        @endif

                        @if($subGroupFirst)
                            @php
                                $rowsToSpan = min($subGroupRowsRemaining, $rowsPerPage - $currentPageRows + 1);
                                if ($rowsToSpan < 1) $rowsToSpan = 1;
                            @endphp
                            <td rowspan="{{ $rowsToSpan }}" style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle; display: table-cell; width: 10% !important;">
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                    {{ strtoupper($subGroupName) }}
                                </div>
                            </td>
                            @php $subGroupFirst = false; @endphp
                        @endif

                        <td style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle;">{{ strtoupper($account['client_account_number']) }}</td>
                        <td style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle;">{{ strtoupper($account['client_account_name']) }}</td>
                        <td style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle;">{{ strtoupper($account['currency_symbol']) }}</td>
                        <td style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle;">{{ $account['deleted_at'] == null ? 'ACTIVE' : 'CLOSED' }}</td>
                        <td style="border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle;">{{ strtoupper($account['created_by']) }}</td>
                    </tr>

                    @php
                        $masterRowsRemaining--;
                        $groupRowsRemaining--;
                        $subGroupRowsRemaining--;

                        if ($masterRowsRemaining > 0 && $currentPageRows >= $rowsPerPage) {
                            $masterContinued = true;
                        }
                        if ($groupRowsRemaining > 0 && $currentPageRows >= $rowsPerPage) {
                            $groupContinued = true;
                        }
                    @endphp
                @endforeach
            @endforeach
        @endforeach
    @endforeach
    </tbody>
</table>
</body>
</html>
