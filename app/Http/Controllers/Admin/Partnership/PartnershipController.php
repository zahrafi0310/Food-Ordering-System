<?php

namespace App\Http\Controllers\Admin\Partnership;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PartnershipController extends Controller
{
    public function __construct(private ImageService $imageService)
    {
    }

    public function index() : View
    {
        $partner = Partnership::all();

        return view('company.admin.partnership.index', ['partner' => $partner]);
    }

    /*
    *  Function to view create file
    */
    public function create() : View
    {
        return view('company.admin.partnership.create');
    }

    /*
    *  Function to store data into partnership table
    */
    public function store(Request $request) : RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,svg|max:10480',
            'company_name' => 'required|max:255',
            'owner_name' => 'required|max:255',
            'date' => 'required|date',
            'location' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $imagePath = $this->imageService->store($request->file('image'), 'images/partnership');

        $created = Partnership::create([
            'image' => $imagePath,
            'company_name' => $request->company_name,
            'owner_name' => $request->owner_name,
            'date_join' => $request->date,
            'location' => $request->location,
        ]);

        Log::info([$created]);

        return back()->with('success-message', 'Partner added successfully.');
    }

    /*
    *  Function to view edit file
    */
    public function edit($id) : View
    {
        $partner = Partnership::findOrFail($id);

        Log::info([$partner]);

        return view('company.admin.partnership.edit', ['partner' => $partner]);
    }

    /*
    *  Function to update partnership resource
    */
    public function update(Request $request, $id) : RedirectResponse
    {
        $partner = Partnership::findOrFail($id);

        if ($request->anyFilled(['image', 'company_name', 'owner_name', 'date', 'location'])) {

            $updateData = [];

            if ($request->hasFile('image')) {

                $imagePath = $this->imageService->replace($request->file('image'), 'images/partnership', $partner->image);

                $updateData['image'] = $imagePath;
            }

            $updateData = array_merge(
                $updateData,
                $this->getFilledData($request, [
                    'company_name',
                    'owner_name',
                    'date',
                    'location',
                ])
            );

            $updated = $partner->update($updateData);

            Log::info([$updateData, $updated]);

            return redirect()->route('partnership')->with('success-message', 'Your changes are saved successfully.');
        }
        else {
            return back()->withErrors([
                'error-message' => 'Please insert data to update partnership.',
            ]);
        }
    }

    public function destroy($id) : RedirectResponse
    {
        $partnership = Partnership::findOrFail($id);

        $this->imageService->delete($partnership->image);

        $partnership->delete();

        Log::info([$partnership]);

        return back()->with('success-message', 'Partner is deleted successfully.');
    }
}
