<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengguna;
use App\Services\SimpegApiService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $simpegApi;

    public function __construct(SimpegApiService $simpegApi)
    {
        $this->simpegApi = $simpegApi;
    }

    public function login(Request $request)
    {
        $request->validate([
             'nip' => 'required|string',
            'password' => 'required|string',
        ]);

        $nip = $request->nip;
        $password = $request->password;

        $pengguna = Pengguna::where('nip', $nip)->first();

        if (!$pengguna) {
            // Cek SIMPEG jika NIP belum ada di tabel pengguna
            $pegawai = $this->simpegApi->getPegawaiByNip($nip);

            if (!$pegawai) {
                return response()->json([
                    'message' => 'NIP tidak ditemukan di sistem SIMPEG.'
                ], 401);
            }

            $defaultPassword = substr($nip, -8);

            // Default password logic for first time login
            if ($password !== $defaultPassword) {
                return response()->json([
                    'message' => 'NIP ditemukan di SIMPEG, tetapi password default salah. Gunakan 8 angka terakhir NIP Anda untuk login pertama kali.'
                ], 401);
            }

            // Create user
            $pengguna = Pengguna::create([
                'nama_lengkap' => $pegawai['nama_lengkap'],
                'nip' => $nip,
                'kata_sandi_hash' => Hash::make($password),
                'peran' => 'peserta', // Default
                'jabatan' => $pegawai['jabatan'],
                'rumpun_jabatan' => $pegawai['rumpun_jabatan'],
                'unit_kerja' => $pegawai['unit_kerja'],
                'status' => 'aktif',
            ]);
        } else {
            // User sudah ada, cek password
            if (!Hash::check($password, $pengguna->kata_sandi_hash)) {
                return response()->json([
                    'message' => 'Password salah.'
                ], 401);
            }

            // Cek apakah akun aktif
            if ($pengguna->status !== 'aktif') {
                return response()->json([
                    'message' => 'Akun Anda tidak aktif.'
                ], 403);
            }

            // Sinkronisasi data dengan SIMPEG agar selalu mutakhir
            $pegawai = $this->simpegApi->getPegawaiByNip($nip);
            if ($pegawai) {
                $pengguna->update([
                    'nama_lengkap' => $pegawai['nama_lengkap'],
                    'jabatan' => $pegawai['jabatan'],
                    'rumpun_jabatan' => $pegawai['rumpun_jabatan'],
                    'unit_kerja' => $pegawai['unit_kerja'],
                ]);
            }
        }

        // Generate token Sanctum
        $token = $pengguna->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $pengguna
        ]);
    }
    
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    public function changePasswordRequestOtp(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
        ]);

        $pengguna = $request->user();

        // Update email if provided
        if ($request->has('email') && !empty($request->email)) {
            $pengguna->email = $request->email;
            $pengguna->save();
        }

        if (empty($pengguna->email)) {
            return response()->json([
                'message' => 'Email belum diatur. Silakan masukkan email Anda.'
            ], 400);
        }

        $otp = (string) rand(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('otp_change_' . $pengguna->pengguna_id, $otp, now()->addMinutes(10));

        try {
            \Illuminate\Support\Facades\Mail::to($pengguna->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim email OTP. Pastikan konfigurasi SMTP sudah benar.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda.'
        ]);
    }

    public function changePasswordVerify(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'password_sebelumnya' => 'required|string',
            'password_baru' => 'required|string|min:8|confirmed',
        ]);

        $pengguna = $request->user();

        // Validasi password lama
        if (!Hash::check($request->password_sebelumnya, $pengguna->kata_sandi_hash)) {
            return response()->json([
                'message' => 'Password sebelumnya tidak sesuai.'
            ], 400);
        }

        // Validasi OTP
        $cachedOtp = \Illuminate\Support\Facades\Cache::get('otp_change_' . $pengguna->pengguna_id);
        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'
            ], 400);
        }

        if (Hash::check($request->password_baru, $pengguna->kata_sandi_hash)) {
            return response()->json([
                'message' => 'Password baru tidak boleh sama dengan password sebelumnya.'
            ], 400);
        }

        $pengguna->update([
            'kata_sandi_hash' => Hash::make($request->password_baru)
        ]);

        \Illuminate\Support\Facades\Cache::forget('otp_change_' . $pengguna->pengguna_id);

        return response()->json([
            'message' => 'Password berhasil diubah.'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'email' => 'required|email',
        ]);

        $pengguna = Pengguna::where('nip', $request->nip)->first();

        if (!$pengguna) {
            return response()->json([
                'message' => 'NIP tidak ditemukan.'
            ], 404);
        }

        // Jika email di database kosong, simpan email yang baru dimasukkan.
        // Jika sudah ada, pastikan email yang dimasukkan cocok dengan database.
        if (empty($pengguna->email)) {
            $pengguna->email = $request->email;
            $pengguna->save();
        } else {
            if ($pengguna->email !== $request->email) {
                return response()->json([
                    'message' => 'Email tidak cocok dengan data pengguna yang terdaftar.'
                ], 400);
            }
        }

        $otp = (string) rand(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('otp_reset_' . $pengguna->nip, $otp, now()->addMinutes(10));

        try {
            \Illuminate\Support\Facades\Mail::to($pengguna->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim email OTP. Pastikan konfigurasi SMTP sudah benar.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'otp' => 'required|string',
            'password_baru' => 'required|string|min:8|confirmed',
        ]);

        $pengguna = Pengguna::where('nip', $request->nip)->first();

        if (!$pengguna) {
            return response()->json([
                'message' => 'NIP tidak ditemukan.'
            ], 404);
        }

        $cachedOtp = \Illuminate\Support\Facades\Cache::get('otp_reset_' . $pengguna->nip);
        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'
            ], 400);
        }

        $pengguna->update([
            'kata_sandi_hash' => Hash::make($request->password_baru)
        ]);

        \Illuminate\Support\Facades\Cache::forget('otp_reset_' . $pengguna->nip);

        return response()->json([
            'message' => 'Password berhasil di-reset! Silakan login dengan password baru Anda.'
        ]);
    }
}
