<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat Pelatihan</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            text-align: center;
            color: #1D315F;
            margin: 0;
            padding: 0;
            background-color: #F9FBFC;
        }
        .container {
            border: 10px solid #006A63;
            padding: 50px;
            margin: 20px;
            background-color: white;
            border-radius: 10px;
        }
        .header {
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #6B7280;
            margin-bottom: 20px;
        }
        .title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 40px;
        }
        .subtitle {
            font-size: 18px;
            color: #4B5563;
            margin-bottom: 10px;
        }
        .name {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #1D315F;
            text-decoration: underline;
        }
        .nip {
            font-size: 16px;
            color: #6B7280;
            margin-bottom: 5px;
        }
        .unit-kerja {
            font-size: 15px;
            color: #4B5563;
            margin-bottom: 35px;
            font-style: italic;
        }
        .details-container {
            width: 100%;
            margin-bottom: 40px;
        }
        .details-table {
            width: 80%;
            margin: 0 auto;
        }
        .details-table td {
            text-align: center;
            width: 50%;
        }
        .detail-label {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 24px;
            font-weight: bold;
            color: #1D315F;
        }
        .footer {
            margin-top: 50px;
            font-size: 14px;
            color: #9CA3AF;
        }
        .signature-area {
            margin-top: 50px;
            text-align: right;
            padding-right: 50px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Sertifikat Kelulusan</div>
        
        <div class="title">{{ $sertifikat['judul_pembelajaran'] ?? 'Pelatihan' }}</div>
        
        <div class="subtitle">Diberikan Kepada:</div>
        <div class="name">{{ $sertifikat['nama_peserta'] ?? 'Nama Peserta' }}</div>
        <div class="nip">NIP: {{ $sertifikat['nip'] ?? '-' }}</div>
        <div class="unit-kerja">{{ $sertifikat['unit_kerja'] ?? '-' }}</div>
        
        <div class="details-container">
            <table class="details-table">
                <tr>
                    <td>
                        <div class="detail-label">Total Jam Pelajaran (JPL)</div>
                        <div class="detail-value">{{ $sertifikat['jpl'] ?? 0 }} Jam</div>
                    </td>
                    <td>
                        <div class="detail-label">Tanggal Terbit</div>
                        <div class="detail-value">{{ $sertifikat['tanggal'] ?? date('d F Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="signature-area">
            <div>Disahkan Oleh,</div>
            <br><br><br>
            <div class="signature-name">Pejabat Pembina Kepegawaian</div>
        </div>

        <div class="footer">
            Nomor Sertifikat: {{ $sertifikat['nomor_sertifikat'] ?? '-' }}<br>
            Dokumen ini diterbitkan oleh Sistem Pembelajaran BKPSDM secara elektronik.
        </div>
    </div>
</body>
</html>
