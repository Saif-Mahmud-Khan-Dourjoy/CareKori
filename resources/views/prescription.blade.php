{{-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Prescription</title>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #ffffff;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .banner-top-container {
            position: relative;
            height: 100px;
        }

        .banner-top-bg {
            height: 100px;
            border-bottom-left-radius: 99px;
            width: 50%;
            position: absolute;
            top: 0;
            right: 0;
            background-color: #e3f1fc;
        }

        .banner-top {
            background-color: #6DB5F4;
            height: 80px;
            border-bottom-left-radius: 99px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: end;


            /* padding: 25px 30px 0 0; */
            font-weight: bold;
            letter-spacing: 2px;
            position: absolute;
            top: 0;
            right: 0;
            width: 49%;

        }

        .banner-text {
            margin-right: 30px;
            font-size: 24px;
        }

        .main {
            padding:10px  40px 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;

        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left img {
            height: 80px;
        }

        .header-right {
            text-align: right;
        }

        .header-right h2 {
            margin: 0;
            color: #104e8b;
        }

        .section {
            margin-top: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            /* margin-bottom: 10px; */
        }

        .info-col {
            width: 48%;
        }

        .rx-title {
            /* margin-top: 10px; */
            font-size: 24px;
            font-weight: bold;
            color: #104e8b;
        }

        .med-list {
            margin-top: 10px;
            flex-grow: 1;
        }

        .med-item {
            margin-bottom: 5px;
            font-size: 16px;
        }

        .signature {
            margin-top: auto;
            text-align: right;
            font-size: 16px;
        }

        .watermark {
            position: absolute;
            top: 80%;
            left: 50%;
            transform: translate(-50%, -80%);
            opacity: 0.07;
            z-index: 0;
        }

        .watermark img {
            height: 250px;
        }

        .footer-container {
            position: relative;
            height: 100px;
        }

        .footer-bg {
            background-color: #e3f1fc;
            height: 120px;
            border-top-right-radius: 99px;
            width: 99%;
            position: absolute;
            bottom: 0;
        }

        .footer {
            background-color: #6DB5F4;
            color: white;
            font-size: 14px;
            display: flex;
             justify-content: center;
            align-items: center;
            /* padding: 20px 30px; */
            border-top-right-radius: 99px;
            position: absolute;
            bottom: 0;
            width: 98%;
            height: 100px
        }

        .footer-div {
            display: flex;
            align-items: center;
           
            gap: 50px;
           
        }

        .footer-info {
            display: flex;
            gap: 80px;
            
        }

        .qr-code {
            height: 50px;
        }

        .contact-info,
        .address-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>
</head>

<body>

    <div class="banner-top-container">
        <div class="banner-top-bg"></div>
        <div class="banner-top">
            <div class="banner-text">
                Prescription
            </div>
        </div>
    </div>

    <div class="main">
        <div class="header">
            <div class="header-left">
                <img src="testlogo.jpg" alt="">
            </div>
            <div class="header-right">
                <h2>Dr. John Doe</h2>
                <p>Urology</p>
                <p>Tagline</p>
            </div>
        </div>

        <div class="section">
            <div class="info-row">
                <div class="info-col">
                    <p><strong>Patient Name:</strong> John Smith</p>
                    <p><strong>Address:</strong> 123 Main St, Anytown, USA</p>
                    <p><strong>Date:</strong> 10th Sept</p>
                </div>
                <div class="info-col">
                    <p><strong>Insurance:</strong> Health Insurance</p>
                    <p><strong>Diagnosis:</strong> Hypertension</p>
                </div>
            </div>
        </div>

        <div class="rx-title">Rx</div>


        <div class="med-list">
            <div class="med-item">
                • Paracetamol — 1-0-1 (After Meal)
            </div>
            <div class="med-item">
                • Amoxicillin — 1-1-1 (Before Meal)
            </div>
            <div class="med-item">
                • Cetirizine — 0-0-1
            </div>
        </div>


        <div class="signature">
            <p>Signature _____________________</p>
        </div>

        <div class="watermark">
            <img src="testlogo.jpg" alt="">
        </div>
    </div>

    <div class="footer-container">
        <div class="footer-bg"></div>
        <div class="footer">
            <div class="footer-div">

                <div>
                    <img class="qr-code" src="qr.png" alt="QR Code">
                </div>
                <div class="footer-info">
                    <div class="contact-info">
                        <div>📱 + (00) 123 456 789</div>
                        <div>✉️ company@mail.com</div>
                    </div>
                    <div class="address-info">
                        <div>🌐 www.companyname.com</div>
                        <div>📍 your company address</div>
                    </div>

                </div>
            </div>


        </div>
    </div>

</body>

</html> --}}

{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }

        .header-wrapper {
            background-color: #e3f1fc;
            padding: 0;
            border-bottom-left-radius: 60px;
            width: 50%;
            float: right;
            /* height: 100px; */
        }

        .banner {
            background-color: #6DB5F4;
            color: white;
            text-align: right;
            font-weight: bold;
            font-size: 22px;
            padding: 20px 30px;
            border-bottom-left-radius: 60px;
            width: 98%;
            float: right;
            /* height: 98%; */
        }

        .container {
            padding: 30px;
            clear: both;
        }

        .top-table, .info-table, .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .top-table td {
            vertical-align: top;
        }

        .top-table .doctor-name {
            color: #104e8b;
            font-size: 18px;
            font-weight: bold;
        }

        .rx-title {
            font-size: 20px;
            font-weight: bold;
            color: #104e8b;
            margin-top: 30px;
        }

        .med-list {
            margin-top: 10px;
        }

        .med-item {
            margin-bottom: 5px;
        }

        .signature-section {
            margin-top: 50px;
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 30px;
        }

        .footer-wrapper {
            background-color: #e3f1fc;
            border-top-right-radius: 60px;
            padding: 0;
        }

        .footer-table {
            background-color: #6DB5F4;
            color: white;
            font-size: 12px;
            padding: 20px 30px;
            border-top-right-radius: 60px;
        }

        .footer-table td {
            padding-right: 20px;
            vertical-align: top;
        }

        .qr-code {
            height: 50px;
        }

        .watermark {
            position: fixed;
            top: 35%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
        }

        .watermark img {
            width: 300px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header-wrapper">
    <div class="banner">PRESCRIPTION</div>
</div>

<!-- BODY -->
<div class="container">

    <table class="top-table">
        <tr>
            <td>
                <img src="{{ public_path('images/logo.png') }}" width="80" alt="Logo">
            </td>
            <td style="text-align:right;">
                <div class="doctor-name">Dr. John Doe</div>
                Urology<br>
                Specialist Physician
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <strong>Patient Name:</strong> John Smith<br>
                <strong>Address:</strong> 123 Main Street, City, Country<br>
                <strong>Date:</strong> 10th September 2025
            </td>
            <td style="text-align:right;">
                <strong>Insurance:</strong> Health Insurance<br>
                <strong>Diagnosis:</strong> Hypertension
            </td>
        </tr>
    </table>

    <div class="rx-title">Rx</div>

    <div class="med-list">
        <div class="med-item">• Paracetamol — 1-0-1 (After Meal)</div>
        <div class="med-item">• Amoxicillin — 1-1-1 (Before Meal)</div>
        <div class="med-item">• Cetirizine — 0-0-1</div>
    </div>

    <div class="signature-section">
        <div class="signature-line"></div>
        Signature
    </div>

    <div class="watermark">
        <img src="{{ public_path('images/watermark.png') }}" alt="Watermark">
    </div>

</div>

<!-- FOOTER -->
<div class="footer-wrapper">
    <table class="footer-table">
        <tr>
            <td><img src="{{ public_path('images/qr.png') }}" alt="QR Code" class="qr-code"></td>
            <td>📱 + (00) 123 456 789</td>
            <td>✉️ company@mail.com</td>
            <td>🌐 www.companyname.com</td>
            <td>📍 456 Clinic Road, YourCity</td>
        </tr>
    </table>
</div>

</body>
</html> --}}

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Prescription' }}</title>
    <style>
        @page {
            margin: 270px 40px 120px 40px;
            /* top right bottom left */
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #111;
        }

        header {
            position: fixed;
            top: -270px;
            left: 0;
            right: -40px;
            /* height: 100px;
            border-bottom: 1px solid #ccc;
            text-align: center;
            padding-top: 12px; */
        }

        .header-wrapper {
            background-color: #e3f1fc;
            padding: 0;
            border-bottom-left-radius: 60px;
            width: 50%;
            float: right;
            height: 80px;
        }

        .banner {
            background-color: #6DB5F4;
            border-bottom-left-radius: 60px;
            width: 97%;
            float: right;
            height: 70px;

        }

        .banner-title {
            color: white;
            font-weight: bold;
            text-align: right;
            /* line-height: 100px; */
            font-size: 20px;
            margin-right: 40px;
            margin-top: 20px;
            /* margin-top: -50px; */



        }

        .top-table,
        .info-table,
        {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;


        }



        .top-table td {
            vertical-align: top;
        }

        .top-table .doctor-name {
            color: #104e8b;
            font-size: 16px;
            font-weight: 600;
        }

        footer {
            position: fixed;
            bottom: -120px;
            left: -40px;
            right: 0;



        }

        .footer-wrapper {
            background-color: #e3f1fc;
            border-top-right-radius: 60px;
            height: 100px;
            position: relative;
            padding: 0;
        }

        .footer-content {
            background-color: #6DB5F4;
            border-top-right-radius: 60px;
            height: 90px;
            width: 98%;
            position: absolute;
            bottom: 0;
            left: 0;
        }

        .footer-table {
            width: 100%;
            height: 100%;
            /* table fills footer-content */
            border-collapse: collapse;
            /* text-align: center; */
            /* top: -25%;
            transform: translateY(25%); */
            margin-left: 40px;
            margin-top: 15px;

        }

        .footer-table td {
            vertical-align: middle;
            /* 🔑 forces vertical centering */
            padding: 0 10px;
        }

        .col-img {
            width: 25%;

            /* QR column */
        }

        .col-text {
            width: 30%;
            /* text columns */
            color: white;
        }

        .rx-title {
            font-size: 20px;
            font-weight: bold;
            color: #104e8b;
            margin-top: 20px;
        }

        .med-list {
            margin-top: 10px;
        }

        .med-item {
            margin-bottom: 5px;
        }

        .pagenum:before {
            content: counter(page);
        }

        .pagecount:before {
            content: counter(pages);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 20px;
        }

        p {
            margin: 0 0 8px;
            line-height: 1.5;
        }

        .watermark {
            position: fixed;
            top: 40%;
            /* vertical center */
            left: 45%;
            /* horizontal center */
            transform: translate(-45%, -40%);
            /* perfectly centered */
            height: 100px;
            /* adjust size */
            opacity: 0.1;
            /* less opacity */
            z-index: -1;
            /* keep it behind text */
        }

        /* Reserve space on last page so content won't overlap signature */
        /* .signature-space {
            height: 80px;
            
        } */

        /* Signature pinned above footer */
        /* .signature {
            position: fixed;
            bottom: 100px;
           
            right: 40px;
            font-weight: bold;
        } */

        /* Hide signature everywhere by default */
        /* .signature {
            display: none;
        } */

        /* Show signature only on last page */
        /* body:last-of-type .signature {
            display: block;
        } */
    </style>
</head>

<body>

    <header>
        {{-- <strong>My Company</strong><br>
        123 Anywhere St, City — support@example.com --}}
        <div class="header-wrapper">
            <div class="banner">
                <div class="banner-title"> PRESCRIPTION</div>
            </div>
        </div>
        <div style="clear: both;">
            <table class="top-table">
                <tr>
                    <td>
                        <img src="{{ public_path('images/network.png') }}" height="60" alt="Logo">
                    </td>
                    <td style="text-align:right; padding-right: 40px;">
                        <div class="doctor-name">{{ $provider->name }}</div>
                        {{ $speciality->specialized_at }}<br>
                        <span style="font-weight: 600">Tagline:</span> N/A
                    </td>
                </tr>
            </table>

            <table class="info-table">
                <tr>
                    <td>
                        <span style="font-weight: 500">Patient Name:</span> {{ $customer->name }}<br>
                        <span style="font-weight: 500">Address:</span> {{ $customer_profile->address }}<br>
                        <span style="font-weight: 500">Date:</span> {{ $date }}
                    </td>
                    <td style="text-align:right; padding-right: 40px;">
                        <span style="font-weight: 500">Insurance:</span> {{ $insurance ?? 'N/A' }}<br>
                        <span style="font-weight: 500">Diagnosis:</span> {{ $diagnosis ?? 'N/A' }}
                    </td>
                </tr>
            </table>
        </div>
        <div class="rx-title">Rx</div>

    </header>

    <footer>
        <div style="text-align:right; margin-bottom: 20px;">
            <span style="font-weight: 600">Signature</span> _____________________

        </div>
        @php
            use SimpleSoftwareIO\QrCode\Facades\QrCode;

            $qrData = base64_encode(
                QrCode::format('svg')
                    ->size(60)
                    ->generate($provider->name . ' - ' . $speciality->specialized_at),
            );

        @endphp
        <div class="footer-wrapper">
            <div class="footer-content">
                <table class="footer-table">
                    <tbody>
                        <tr>
                            <td class="col-img">
                                <img src="data:image/svg+xml;base64,{{ $qrData }}" style=" height: 60px;">
                            </td>
                            <td class="col-text">
                                <div>+880734763474</div>
                                <div>company@gmail.com</div>
                            </td>
                            <td class="col-text">
                                <div>www.company.com</div>
                                <div>Dhaka, Bangladesh</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        {{-- <div>© {{ date('Y') }} My Company — All rights reserved.</div>
        <div>Page <span class="pagenum"></span> of <span class="pagecount"></span></div> --}}
    </footer>
    <img src="{{ public_path('images/network.png') }}" class="watermark" alt="Watermark">

    <div>


        <div class="med-list">
          
            @foreach ($medicine as $medication)
                <div class="med-item">• {{ $medication['name'] }} — {{ $medication['dosage'] }} ({{ $medication['timing'] }})</div>
            @endforeach

            @if (!empty($tests))
            <div style="margin-top: 15px; font-size: 16px; font-weight: 500" class="rx-title"> Tests: </div>
                <div class="med-item" style="margin-top: 5px">
                    @foreach ($tests as $test)
                        • {{ $test['name'] }}<br>
                    @endforeach
                </div>
            @endif

            @if (!empty($advice))
            <div style="margin-top: 15px;font-size: 16px; font-weight: 500" class="rx-title"> Advice: </div>
                <div class="med-item" style="margin-top: 5px">
                    @foreach ($advice as $advice)
                        • {{ $advice['advice'] }}<br>
                    @endforeach
                </div>
            @endif
           

        </div>

        


    </div>



</body>

</html>
