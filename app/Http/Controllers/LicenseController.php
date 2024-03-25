<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\License;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class LicenseController extends Controller
{
    public function index()
    {
        // Dapatkan semua lisensi
        $licenses = License::all();
        $companies = Company::get();

        // Hitung jumlah lisensi
        $license_count = License::count();

        // Dapatkan lisensi yang aktif (belum kadaluarsa)
        $activeLicenses = License::where('expires_at', '>', Carbon::now())->get();

        // Dapatkan lisensi yang kadaluarsa
        $expiredLicenses = License::where('expires_at', '<=', Carbon::now())->get();

        // Dapatkan lisensi yang dibuat pada tahun ini
        $licensesThisYear = License::whereYear('purchase_date', Carbon::now()->year)->get();

        // Hitung jumlah lisensi aktif dan kadaluarsa
        $activeLicenseCount = $activeLicenses->count();
        $expiredLicenseCount = $expiredLicenses->count();
        $licensesThisYearCount = $licensesThisYear->count();

        return view('licenses.index', compact('licenses', 'license_count', 
        'activeLicenses', 'expiredLicenses', 'activeLicenseCount', 
        'expiredLicenseCount', 'licensesThisYear', 'licensesThisYearCount', 'companies'));
    }

    public function generate(Request $request)
    {
        try {
            // Validasi request
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'quantity' => 'required|integer|min:1|max:1000',
                'validity_period' => 'required|integer|in:30,90,365', // Menentukan masa berlaku lisensi dalam hari (1, 7, atau 30)
            ]);

            // Jika validasi gagal, kirim respons dengan pesan kesalahan
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            // Dapatkan data perusahaan berdasarkan ID
            $company = Company::findOrFail($request->company_id);

            // Inisialisasi array untuk menyimpan lisensi
            $licenses = [];

            // Buat order untuk lisensi
            $order = Order::create([
                'company_id' => $company->id,
                'quantity' => $request->quantity,
                'price' => $request->quantity * 1000, // Harga per lisensi
            ]);

            // Loop sebanyak quantity untuk membuat lisensi
            for ($i = 0; $i < $request->quantity; $i++) {
                // Bagian acak dari lisensi
                $randomPart1 = strtoupper(substr(uniqid(), -4));
                $randomPart2 = strtoupper(substr(uniqid(), -4));
                $privateKey = "PRSMX";

                // Gabungkan private key dengan bagian acak
                $license = "PRSMX" . '-' . $randomPart1 . '-' . $randomPart2;

                // Tambahkan hash ke lisensi
                $hash = hash('sha256', $license);
                $license_key = $license . '-' . strtoupper(substr($hash, 0, 4));

                // Tentukan tanggal pembelian
                $purchase_date = now();

                // Tentukan tanggal kadaluarsa berdasarkan masa berlaku
                $valid_until = $request->validity_period;
                $expires_at = now()->addDays((int)$request->validity_period);

                // Simpan lisensi ke dalam array
                $licenses[] = License::create([
                    'company_id' => $company->id,
                    'order_id' => $order->id, // Masukkan order_id
                    'license_key' => $license_key,
                    'purchase_date' => $purchase_date,
                    'valid_until' => $valid_until,
                    'expires_at' => $expires_at,
                ]);
            }

            // Berikan respons dengan data lisensi yang baru dibuat
            return response()->json(['licenses' => $licenses], 201);
        } catch (\Exception $e) {
            // Tangani kesalahan dan kirim respons dengan pesan kesalahan yang sesuai
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function verify(Request $request)
    {
        // Validasi request
        $request->validate([
            'license' => 'required',
        ]);

        // Dapatkan lisensi dari request
        $license = $request->license;

        // Pisahkan bagian-bagian dari lisensi
        $parts = explode('-', $license);
        $randomParts = array_slice($parts, 1, 2); // Hanya bagian acak yang perlu diverifikasi
        $providedHash = end($parts);

        // Gabungkan bagian acak dengan private key yang baru
        $privateKey = "PRSMX";
        $licenseWithoutHash = strtoupper($privateKey) . '-' . strtoupper(implode('-', $randomParts)); // Konversi ke huruf besar

        // Hitung hash dari lisensi tanpa hash yang diberikan
        $calculatedHash = hash('sha256', $licenseWithoutHash);
        $calculatedHash = strtoupper(substr($calculatedHash, 0, 4)); // Konversi ke huruf besar

        // Periksa apakah hash yang diberikan cocok dengan hash yang dihitung
        if ($providedHash === $calculatedHash) {
            // Cari lisensi berdasarkan kunci lisensi
            $license = License::where('license_key', $request->license)->first();
            // Jika lisensi tidak ditemukan, berikan respons bahwa lisensi tidak valid
            if (!$license) {
                return response()->json(['message' => 'Invalid license key'], 404);
            } else {
                // Periksa apakah lisensi masih aktif
                if ($license->expires_at > now()) {
                    return response()->json(['valid' => true, 'active' => true], 200);
                } else {
                    return response()->json(['valid' => true, 'active' => false], 200);
                }
            }
        } else {
            return response()->json(['valid' => false, 'active' => false], 200); // Lisensi tidak valid
        }
    }

    public function list(Request $request)
    {
        try {
            // Mendapatkan semua lisensi beserta nama perusahaannya
            $licenses = License::with('company')->orderBy('id', 'desc')->get();

            // Memformat data lisensi dengan nama perusahaan
            $licensesArray = $licenses->map(function ($license) {
                return [
                    'id' => $license->id,
                    'company_name' => $license->company->name_company,
                    'order_id' => $license->order_id,
                    'license_key' => $license->license_key,
                    'purchase_date' => $license->purchase_date,
                    'valid_until' => $license->valid_until,
                    'expires_at' => $license->expires_at,
                    'created_at' => $license->created_at,
                    'updated_at' => $license->updated_at
                ];
            });

            return response()->json([
                'data' => $licensesArray
            ]);
        } catch (\Exception $e) {
            // Tangani kesalahan dan kirim respons dengan pesan kesalahan yang sesuai
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
