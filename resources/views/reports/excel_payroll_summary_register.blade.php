<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Monthly Payroll Summary Register</title>
    @php echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Payroll Summary</x:Name><x:WorksheetOptions><x:DisplayGridlines/><x:Print><x:ValidPrinterInfo/><x:Orientation>Landscape</x:Orientation></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->"; @endphp
    <style>
        body { font-family: Calibri, -apple-system, BlinkMacSystemFont, Arial, sans-serif; font-size: 11pt; color: #000; margin: 15px; }
        table { border-collapse: collapse !important; width: 100%; margin-bottom: 24px; font-family: Calibri, Arial, sans-serif; }
        th, td { border: 1px solid #000000; padding: 5px 8px; font-size: 11pt; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-red { color: #dc2626 !important; font-weight: bold; }
        .th-cell { font-weight: bold; text-align: center; background: #ffffff; border: 1px solid #000000; }
        
        .no-border-cell { border-top: none !important; border-bottom: none !important; border-left: none !important; border-right: none !important; }
        .header-title { font-size: 12pt; font-weight: bold; text-align: center; padding: 6px; }
        .dept-title { font-size: 11pt; font-weight: bold; text-align: left; }
        .month-title { font-size: 11pt; font-weight: bold; text-align: right; }
        
        @media print {
            .no-print { display: none !important; }
            body { margin: 5mm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body>
    @if(empty($isExcel))
    <div class="no-print" style="margin-bottom:15px;display:flex;justify-content:space-between;align-items:center">
        <a href="{{ route('reports.index') }}" style="padding:7px 16px;background:#64748b;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;font-size:12.5px">&larr; Back to Reports</a>
        <button onclick="window.print()" style="padding:8px 22px;background:#0284c7;color:#fff;border:none;border-radius:6px;font-weight:bold;font-size:13px;cursor:pointer">🖨️ Print / Save as PDF</button>
    </div>
    @endif

    <table>
        <tbody>
            <!-- Row 1: Company Name & Address From Date To Date -->
            <tr>
                <td colspan="13" class="no-border-cell header-title" style="text-align:center;font-size:12pt;font-weight:bold;padding:10px 4px">
                    {{ strtoupper($companyName) }} &amp; {{ strtoupper($companyAddress) }} FROM {{ $from->format('d-m-Y') }} TO {{ $to->format('d-m-Y') }}
                </td>
            </tr>
            <tr>
                <td colspan="13" class="no-border-cell" style="height:12px"></td>
            </tr>

            @foreach($departmentsData as $dept)
                <!-- Department Header Row -->
                <tr>
                    <td colspan="6" class="no-border-cell dept-title" style="font-weight:bold;padding:6px 2px">
                        Department :- {{ $dept['department'] }}
                    </td>
                    <td colspan="7" class="no-border-cell month-title" style="font-weight:bold;text-align:right;padding:6px 2px">
                        For The Month Of {{ $from->format('F - Y') }}
                    </td>
                </tr>
                <tr>
                    <td colspan="13" class="no-border-cell" style="height:4px"></td>
                </tr>

                <!-- Table Header Matching Screenshot -->
                <tr>
                    <th rowspan="2" class="th-cell" style="width:45px">SR.<br>NO.</th>
                    <th rowspan="2" class="th-cell" style="min-width:180px">NAME</th>
                    <th rowspan="2" class="th-cell" style="width:40px">P</th>
                    <th rowspan="2" class="th-cell" style="width:40px">A</th>
                    <th rowspan="2" class="th-cell" style="width:40px">WO</th>
                    <th rowspan="2" class="th-cell" style="width:40px">WOP</th>
                    <th rowspan="2" class="th-cell" style="width:40px">H</th>
                    <th rowspan="2" class="th-cell" style="width:40px">HOP</th>
                    <th rowspan="2" class="th-cell" style="width:65px">TOTAL<br>PR.DAYS</th>
                    <th rowspan="2" class="th-cell" style="width:85px">SALARY<br>RATE</th>
                    <th colspan="2" class="th-cell" style="text-align:center">EARNING</th>
                    <th rowspan="2" class="th-cell" style="width:90px">NET<br>PAYABLE</th>
                </tr>
                <tr>
                    <th class="th-cell" style="width:85px">GROSS<br>SALARY</th>
                    <th class="th-cell" style="width:85px">INCENTIVE<br>OTHERS</th>
                </tr>

                @forelse($dept['rows'] as $idx => $r)
                    <tr>
                        <td class="text-center" style="border:1px solid #000">{{ $idx + 1 }}</td>
                        <td class="text-left" style="border:1px solid #000;font-weight:500">{{ strtoupper($r['name']) }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ $r['p'] }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ $r['a'] }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ $r['wo'] }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ $r['wop'] }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ $r['h'] }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ $r['hop'] }}</td>
                        <td class="text-center fw-bold" style="border:1px solid #000">{{ $r['total_pr_days'] }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ number_format($r['salary_rate'], 0) }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ number_format($r['gross'], 1, '.', '') }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ number_format($r['incentive'], 0) }}</td>
                        <td class="text-center" style="border:1px solid #000">{{ number_format($r['net'], 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center" style="border:1px solid #000;color:#64748b;padding:12px">No employees in this department.</td>
                    </tr>
                @endforelse

                <!-- Department Total Row Matching Screenshot (Solid borders, Red text) -->
                <tr>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-left text-red" style="border:1px solid #000">TOTAL</td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center text-red" style="border:1px solid #000">{{ number_format($dept['dept_gross'], 1, '.', '') }}</td>
                    <td class="text-center" style="border:1px solid #000"></td>
                    <td class="text-center text-red" style="border:1px solid #000">{{ number_format($dept['dept_net'], 0) }}</td>
                </tr>
                <tr>
                    <td colspan="13" class="no-border-cell" style="height:18px"></td>
                </tr>
            @endforeach

            <!-- GRAND TOTAL Row Matching Screenshot (Solid borders, Red text) -->
            <tr>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-left text-red" style="border:1px solid #000;font-size:11.5pt">GRAND TOTAL</td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center text-red" style="border:1px solid #000;font-size:11.5pt">{{ number_format($grandGross, 1, '.', '') }}</td>
                <td class="text-center" style="border:1px solid #000"></td>
                <td class="text-center text-red" style="border:1px solid #000;font-size:11.5pt">{{ number_format($grandNet, 0) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
