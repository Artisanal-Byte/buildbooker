<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganisationController extends Controller
{
    public function edit()
    {
        $isDisableGstToggle = false;
        $organisation = Organisation::find(auth()->user()->organisation_id);
        $hasTrancactionsWithGst = $organisation->transactions()->where('receipt_number', 'LIKE', '#G%')->whereNull('transactions.deleted_at')->count() > 0;
        if ($hasTrancactionsWithGst || $organisation->seperate_sequence_for_gst) {
            $isDisableGstToggle = true;
        }
        return Inertia::render('organisation/Edit', ['organisation' => $organisation, "disabled" => $isDisableGstToggle]);
    }


    public function update(Request $request)
    {

        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|max:255|email',
            'mobile' => 'required|regex:/^[0-9]{10}$/',
            'logo'   => $request->hasFile('logo')
                ? 'image|mimes:jpeg,jpg,png,webp|max:2048'
                : 'nullable',
            // 'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'seperate_sequence_for_gst' => 'nullable|boolean', // Validate GST column
        ]);

        try {
            $organisation = Organisation::find(auth()->user()->organisation_id);
            $hasTrancactionsWithGst = $organisation->transactions()->where('receipt_number', 'LIKE', '#G%')->whereNull('transactions.deleted_at')->count() > 0;
            if ($hasTrancactionsWithGst && $request->seperate_sequence_for_gst == true && $request->isChangeToggleGst == true) {
                throw new Exception('Cannot enable separate sequence for GST as there are existing transactions with GST.');
            } elseif ($hasTrancactionsWithGst && $request->seperate_sequence_for_gst == false && $request->isChangeToggleGst == true) {
                throw new Exception('Cannot disable separate sequence for GST as there are existing transactions with GST.');
            }

            if ($request->hasFile('logo')) {
                $logoPath = processLogoImage($validatedData['logo'], $request->name, 'organisation_logos');
                $organisation->update([
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'mobile' => $validatedData['mobile'],
                    'logo' => $logoPath,
                    'seperate_sequence_for_gst' => $validatedData['seperate_sequence_for_gst'] ?? null, // Update GST column
                ]);
            } else {
                $organisation->update([
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'mobile' => $validatedData['mobile'],
                    'seperate_sequence_for_gst' => $validatedData['seperate_sequence_for_gst'] ?? null, // Update GST column
                ]);
            }

            ToastMagic::success("Organization updated successfully!");
            return redirect()->back()->with('success', 'Organisation updated successfully');
        } catch (Exception $e) {
            return redirect()->back()->withErrors('error', $e->getMessage());
        }
    }
}
