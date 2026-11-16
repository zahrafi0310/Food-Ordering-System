<?php

namespace App\Http\Controllers\Admin\StaffAccount;

use App\Http\Controllers\Controller;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StaffAccountController extends Controller
{
    public function index() : View
    {
        // Only show user that is not admin
        // Paginate user table to 8
        $staff = User::where('role', User::ROLE_STAFF)->paginate(self::PER_PAGE_DEFAULT);

        return view('company.admin.staff-account.index', ['staff' => $staff]);
    }




    /*
    * Function to search resource in index page
    */
    public function searchIndex(Request $request) : View
    {
        $keyword = $request->input('search');

        $search = $this->applyLikeSearch(
            User::where('role', User::ROLE_STAFF),
            $keyword,
            [
                'staff_id',
                'name',
                'email',
                'phone',
            ]
        )->paginate(self::PER_PAGE_DEFAULT);

        Log::info([$keyword, $search]);

        return view('company.admin.staff-account.index', ['staff' => $search]);
    }




    /*
    * To view create file
    */
    public function create() : View
    {
        $staffid = StaffAccount::paginate(self::PER_PAGE_COMPACT);

        return view('company.admin.staff-account.create', ['staffid' => $staffid]);
    }




    /*
    * Function to search resource in create page
    */
    public function searchCreate(Request $request) : View
    {
        $keyword = $request->input('search');

        $search = $this->applyLikeSearch(
            StaffAccount::query(),
            $keyword,
            [
                'staff_account_id',
                'created_at',
                'updated_at',
            ]
        )->paginate(self::PER_PAGE_COMPACT);

        Log::info([$keyword, $search]);

        return view('company.admin.staff-account.create', ['staffid' => $search]);
    }




    /*
    * Function to add new staff ID
    */
    public function store(Request $request) : RedirectResponse
    {
        $validator = Validator::make($request->only('new_staff_id'), [
            'new_staff_id' => 'required|min:10|max:12'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Retrieve a submitted input of staff id
        $validated = $validator->safe()->only('new_staff_id');

        // Check if id exists in staff account table
        $staffCheck = StaffAccount::where('staff_account_id', $validated)->exists();

        Log::info(['Input enter by user: ', $validated, 'Exists: ', $staffCheck]);

        if ($staffCheck) {
            return back()->withErrors([
                'error-message' => 'Provided ID is already registered',
            ]);
        }
        else {
            $id = StaffAccount::create([
                'staff_account_id' => $validated['new_staff_id'],
            ]);

            Log::info([$id]);

            return back()->with('success-message', 'Successfully added new ID');
        }
    }




    /*
    * Function view show file
    */
    public function show($id) : View
    {
        $user = User::findOrFail($id);

        return view('company.admin.staff-account.show', ['user' => $user]);
    }




    /*
    * Function view edit file
    */
    public function edit($id) : View
    {
        $user = User::findOrFail($id);

        return view('company.admin.staff-account.edit', ['user' => $user]);
    }




    /*
    * Function to update staff account data
    */
    public function update(Request $request, $id) : RedirectResponse
    {
        $user = User::findOrFail($id);

        $updateData = $this->getFilled(
            $request,
            [
                'name',
                'email',
                'phone',
                'position',
                'address',
            ]
        );

        if (!empty($updateData)) {
            $updated = $user->update($updateData);

            Log::info([$updateData, $updated]);

            return redirect()->route('staff-account-show', $user->id)
                ->with('success-message', 'Your changes are saved successfully.');
        }

        return back()->withErrors([
            'error-message' => 'Please insert data to update staff details.'
        ]);
    }




    /*
    * Function to delete staff data
    */
    public function destroy($id) : RedirectResponse
    {
        $staff = User::findOrFail($id);

        $staff->delete();

        Log::info([$staff]);

        return redirect()->route('staff-account')
            ->with('success-message', 'Staff is deleted successfully.');
    }
}