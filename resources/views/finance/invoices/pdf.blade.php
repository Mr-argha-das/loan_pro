
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tax Invoice</title>

<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
    }

    .invoice {
        width: 100%;
        max-width: 1100px;
        margin: 20px auto;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .invoice th,
    .invoice td {
        border: 1px solid #000;
        padding: 6px;
        vertical-align: middle;
        overflow-wrap: break-word;
    }

    .invoice th {
        text-align: center;
    }

    .center {
        text-align: center;
    }

    .right {
        text-align: right;
    }

    .bold {
        font-weight: bold;
    }

    .company-title {
        font-size: 17px;
        font-weight: bold;
    }

    .invoice-title {
        font-size: 15px;
        font-weight: bold;
        text-decoration: underline;
    }

    .no-border {
        border: none !important;
    }

    .amount-words {
        font-weight: bold;
    }

    .signature {
        height: 115px;
        vertical-align: bottom !important;
    }

    @media print {
        body {
            margin: 0;
        }

        .invoice {
            width: 100%;
            margin: 0;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }
    }
</style>
</head>

<body>

<table class="invoice">

    <!-- Company Header -->
    <tr>
        <th colspan="8" class="center">
            <div class="company-title">
                SHEKHAR SAIN &amp; SON'S PVT LTD.
            </div>
            ADDRESS – SHOP NO. 9 RAO MALKHAN SINGH MARKET
            SOHANA ROAD DHARUHERA REWARI (123106)
            <br>
            <span class="invoice-title">TAX INVOICE</span>
            <br>
            <b>Original copy for recipient</b>
        </th>
    </tr>

    <!-- Customer and Invoice Details -->
    <tr>
        <td colspan="5" rowspan="2">
            <b>To,</b><br><br>

            <b>ANDROMEDA SALES &amp; DISTRIBUTION PVT LTD</b>
            <br><br>

            ADD. 1<sup>st</sup> FLOOR SHOP NO – 125, GRAIN MARKET
            <br>
            JAGDHRI, YAMUNA NAGAR, HARYANA 135001
            <br>
            GSTIN: 06AALCS9004J1Z4
            <br>
            PAN NO.: AALCS9004J
        </td>

        <td colspan="3">
            <b>Invoice NO:</b> 2026-27/16
        </td>
    </tr>

    <tr>
        <td colspan="3">
            <b>DATE:</b> 29/08/2026
            <br><br>
            <b>Product:</b>
        </td>
    </tr>

    <!-- Activities Heading -->
    <tr>
        <th colspan="5">Activities</th>
        <th>Rate</th>
        <th colspan="2">AMOUNT</th>
    </tr>

    <tr>
        <td colspan="8">
            <b>Commission for loan disbursal of the below-mentioned cases.</b>
        </td>
    </tr>

    <!-- Invoice Table Heading -->
    <tr>
        <th>APP ID</th>
        <th>Product</th>
        <th>Customer Name</th>
        <th>Loan Amount</th>
        <th>Rate</th>
        <th>Amount</th>
        <th>Bank</th>
        <th>Month</th>
    </tr>

    <!-- Invoice Data -->
    <tr>
        <td class="center">
            ABN_GSTS00000<br>1061450
        </td>

        <td class="center">LAP</td>

        <td>
            <b>SHREE SHYAM BIOMASS</b>
        </td>

        <td class="center">5000000</td>

        <td class="center">1.53%</td>

        <td class="center">76500</td>

        <td class="center">
            Aditya Birla<br>(STSL)
        </td>

        <td class="center">Aug-2026</td>
    </tr>

    <!-- Totals -->
    <tr>
        <td colspan="2" rowspan="4"></td>

        <td class="bold">Total</td>

        <td class="center">XX</td>

        <td class="right">XX</td>

        <td class="bold">76500</td>

        <td colspan="2" rowspan="4"></td>
    </tr>

    <tr>
        <td class="bold">ADD: CGST 9%</td>
        <td colspan="2"></td>
        <td class="center">6885</td>
    </tr>

    <tr>
        <td class="bold">ADD: SGST 9%</td>
        <td colspan="2"></td>
        <td class="center">6885</td>
    </tr>

    <tr>
        <td class="bold">GRAND TOTAL</td>
        <td colspan="2"></td>
        <td class="bold center">90270</td>
    </tr>

    <!-- Amount in Words -->
    <tr>
        <td colspan="8" class="amount-words">
            Amount in Words: Ninety Thousand Two Hundred Seventy Only
        </td>
    </tr>

    <!-- Bank and Signature Section -->
    <tr>
        <td colspan="5">
            <b>Bank:</b> HDFC Bank
            &nbsp;&nbsp;&nbsp;
            <b>IFSC CODE:</b> HDFC0008305
        </td>

        <td colspan="3" rowspan="6" class="signature">
            <b>For: "SHEKHAR SAIN &amp; SON'S PVT LTD"</b>
            <br><br>

            <b>Authorized Signatory</b>
            <br><br><br>

            __________________________
            <br>
            <b>Director</b>
        </td>
    </tr>

    <tr>
        <td colspan="5">
            <b>ACCOUNT NO.</b> 50200109160783
        </td>
    </tr>

    <tr>
        <td colspan="5">
            <b>GSTIN NO.</b> 06ABPCS3311R1ZS
        </td>
    </tr>

    <tr>
        <td colspan="5">
            <b>PAN:</b> ABPCS3311R
        </td>
    </tr>

    <tr>
        <td colspan="5">
            <b>SAC CODE:</b> 997159
        </td>
    </tr>

    <tr>
        <td colspan="5">
            <b>Mode of Payment:</b> Cheque/Draft/Credit
        </td>
    </tr>

    <tr>
        <td colspan="5">
            <b>Favoring:</b> "SHEKHAR SAIN &amp; SON'S PVT LTD"
        </td>
        <td colspan="3"></td>
    </tr>

</table>

</body>
</html>
