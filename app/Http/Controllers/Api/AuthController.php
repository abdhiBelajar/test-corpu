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

    public function changePassword(Request $request)
    {
        // Validasi input: wajib ada password lama, password baru (min 8 karakter), dan konfirmasi password baru
        $request->validate([
            'password_sebelumnya' => 'required|string',
            'password_baru' => 'required|string|min:8|confirmed',
        ]);

        $pengguna = $request->user();

        // Cek apakah password lama yang dimasukkan sesuai dengan yang ada di database
        if (!Hash::check($request->password_sebelumnya, $pengguna->kata_sandi_hash)) {
            return response()->json([
                'message' => 'Password sebelumnya tidak sesuai.'
            ], 400);
        }

        // Pastikan password baru tidak sama persis dengan password lama
        if (Hash::check($request->password_baru, $pengguna->kata_sandi_hash)) {
            return response()->json([
                'message' => 'Password baru tidak boleh sama dengan password sebelumnya.'
            ], 400);
        }

        // Update password baru di database
        $pengguna->update([
            'kata_sandi_hash' => Hash::make($request->password_baru)
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah.'
        ]);
    }

    public function resetPasswordToDefault(Request $request)
    {
        // Validasi hanya butuh NIP (karena simulasi kita belum punya data NIK/Tanggal Lahir dari SIMPEG)
        $request->validate([
            'nip' => 'required|string',
        ]);

        $pengguna = Pengguna::where('nip', $request->nip)->first();

        if (!$pengguna) {
            return response()->json([
                'message' => 'NIP tidak ditemukan di sistem aplikasi E-Learning.'
            ], 404);
        }

        // Kembalikan ke password default (8 angka terakhir NIP)
        $defaultPassword = substr($request->nip, -8);
        
        $pengguna->update([
            'kata_sandi_hash' => Hash::make($defaultPassword)
        ]);

        return response()->json([
            'message' => 'Password berhasil di-reset! Silakan login menggunakan 8 angka terakhir NIP Anda.'
        ]);
    }
}
