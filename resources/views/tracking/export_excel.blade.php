<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Location Report</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body, table {
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            color: #000000;
        }
        .header-company {
            font-size: 14pt;
            font-weight: 700;
            text-align: center;
            height: 32px;
            vertical-align: middle;
        }
        .header-title {
            font-size: 12pt;
            font-weight: 700;
            text-align: center;
            height: 26px;
            vertical-align: middle;
        }
        .header-date {
            font-size: 11pt;
            text-align: center;
            height: 22px;
            vertical-align: middle;
        }
        th.tbl-head {
            background-color: #ffffff;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            height: 28px;
            padding: 4px 8px;
        }
        td.tbl-cell {
            border: 1px solid #d4d4d4;
            padding: 5px 8px;
            vertical-align: middle;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        a.map-link {
            color: #0563c1;
            text-decoration: underline;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <table border="0" cellpadding="0" cellspacing="0">
        <!-- Row 1: Company Name -->
        <tr>
            <td colspan="7" class="header-company">{{ $companyName }}</td>
        </tr>
        <!-- Row 2: Location Timeline Report - Employee Name -->
        <tr>
            <td colspan="7" class="header-title">Location Timeline Report - {{ $empName }}</td>
        </tr>
        <!-- Row 3: Date -->
        <tr>
            <td colspan="7" class="header-date">Date: {{ $formattedDate }}</td>
        </tr>
        <!-- Row 4: Spacer -->
        <tr>
            <td colspan="7" style="height: 14px;"></td>
        </tr>
        <!-- Row 5: Column Headers matching Screenshot 2 -->
        <tr>
            <th class="tbl-head" style="width: 380px;">Address</th>
            <th class="tbl-head" style="width: 170px;">Time</th>
            <th class="tbl-head" style="width: 120px;">Journey Status</th>
            <th class="tbl-head" style="width: 80px;">Points</th>
            <th class="tbl-head" style="width: 90px;">Accuracy</th>
            <th class="tbl-head" style="width: 80px;">Battery</th>
            <th class="tbl-head" style="width: 110px;">View Map</th>
        </tr>
        <!-- Data Rows -->
        @forelse($pings as $ping)
            @php
                $accuracyVal = $ping->accuracy;
                if ($accuracyVal !== null && is_numeric($accuracyVal)) {
                    $accuracyVal = round($accuracyVal, 1);
                } elseif ($accuracyVal === null) {
                    $accuracyVal = '5';
                }
                $batteryVal = ($ping->battery !== null) ? $ping->battery . '%' : '75%';
                $statusVal = $ping->journey_status ?: 'Transit';
                $mapUrl = "https://www.google.com/maps?q={$ping->lat},{$ping->lng}";
            @endphp
            <tr>
                <td class="tbl-cell text-left">{{ $ping->address ?: 'Unknown Location' }}</td>
                <td class="tbl-cell text-center">{{ $ping->formatted_time }}</td>
                <td class="tbl-cell text-center">{{ $statusVal }}</td>
                <td class="tbl-cell text-center">{{ $ping->points ?: 1 }}</td>
                <td class="tbl-cell text-center">{{ $accuracyVal }}</td>
                <td class="tbl-cell text-center">{{ $batteryVal }}</td>
                <td class="tbl-cell text-center">
                    <a class="map-link" href="{{ $mapUrl }}">View Map</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="tbl-cell text-center" style="height: 36px; color: #888888;">
                    No location records found for {{ $empName }} on {{ $formattedDate }}.
                </td>
            </tr>
        @endforelse
    </table>
</body>
</html>
