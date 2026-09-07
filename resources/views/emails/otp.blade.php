<!DOCTYPE html>
<html>
<head>
    <title>Kode OTP Anda</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #333333; text-align: center;">Verifikasi Kode OTP</h2>
        <p style="color: #555555; font-size: 16px; line-height: 1.5;">
            Halo, <br><br>
            Anda telah meminta untuk melakukan perubahan/reset kata sandi. Berikut adalah kode OTP Anda:
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; font-size: 24px; font-weight: bold; color: #ffffff; background-color: #007bff; padding: 10px 20px; border-radius: 5px; letter-spacing: 2px;">
                {{ $otp }}
            </span>
        </div>
        <p style="color: #555555; font-size: 14px; line-height: 1.5;">
            Kode ini berlaku selama 10 menit. <b>Mohon jangan berikan kode ini kepada siapapun</b>, termasuk pihak administrator kami.
            <br><br>
            Jika Anda tidak merasa melakukan permintaan ini, mohon abaikan email ini.
        </p>
        <hr style="border: none; border-top: 1px solid #eeeeee; margin: 30px 0;">
        <p style="text-align: center; color: #999999; font-size: 12px;">
            &copy; {{ date('Y') }} Sistem E-Learning BKPSDM Buleleng
        </p>
    </div>
</body>
</html>
