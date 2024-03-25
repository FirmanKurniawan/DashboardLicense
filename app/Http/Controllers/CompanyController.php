<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        $company_count = Company::count();
        return view('companies.index', compact('companies', 'company_count'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $validatedData = $request->validate([
            'name_company' => 'required|string|max:255',
            'address_company' => 'required|string|max:255',
            'pic_name' => 'required|string|max:255',
            'pic_email' => 'required|string|email|max:255',
            'pic_phone_number' => 'required|string|max:15',
        ]);

        // Buat perusahaan baru
        $company = new Company();
        $company->name_company = $request->name_company;
        $company->address_company = $request->address_company;
        $company->pic_name = $request->pic_name;
        $company->pic_email = $request->pic_email;
        $company->pic_phone_number = $request->pic_phone_number;
        $company->save();

        // Redirect dengan pesan sukses
        return redirect()->route('company.index')->with('success', 'Company added successfully');
    }

    public function list(Request $request)
    {
        $companies = Company::orderBy('id', 'desc')->get();

        // Mengonversi koleksi model menjadi array
        $companiesArray = $companies->toArray();

        return response()->json([
            'data' => $companiesArray
        ]);
    }
}
