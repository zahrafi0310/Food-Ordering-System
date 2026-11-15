<?php

namespace App\Http\Controllers\Admin\FoodMenu;

use App\Http\Controllers\Controller;
use App\Models\FoodCategory;
use App\Models\FoodMenu;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class FoodMenuController extends Controller
{
    public function __construct(private ImageService $imageService)
    {
    }

    public function index() : View
    {
        $food = FoodMenu::paginate(self::PER_PAGE_DEFAULT);

        return view('company.admin.food-menu.index', ['food' => $food]);
    }

    /*
    *  Function to view create file
    */
    public function create() : View
    {
        $newCategory = FoodCategory::paginate(self::PER_PAGE_COMPACT);

        return view('company.admin.food-menu.create', ['newCategory' => $newCategory]);
    }

    /*
    *  Function to search resource in index page
    */
    public function searchIndex(Request $request) : View
    {
        $keyword = $request->input('search');

        $search = $this->applyLikeSearch(
            FoodMenu::query(),
            $keyword,
            [
                'name',
                'description',
                'price',
                'category_id',
                'image',
            ]
        )->paginate(self::PER_PAGE_DEFAULT);

        return view('company.admin.food-menu.index', ['food' => $search]);
    }

    

    /*
    *  Function to search reasource in create page
    */
    public function searchCreate(Request $request) : View
    {
        $keyword = $request->input('search');

        $search = $this->applyLikeSearch(
            FoodCategory::query(),
            $keyword,
            [
                'name',
                'created_at',
                'updated_at',
            ]
        )->paginate(self::PER_PAGE_COMPACT);

        Log::info([$keyword, $search]);

        return view('company.admin.food-menu.create', ['newCategory' => $search]);
    }

    

    /*
    *  Function to store data into Food Menu table
    */
    public function store(Request $request) : RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'food_name' => 'required|max:255',
            'food_description' => 'required|max:9999',
            'price' => 'required|numeric|decimal:2|min:0.01|max:9999.99',
            'category_id' => 'required|exists:food_categories,id',
            'image' => 'required|image|mimes:jpg,jpeg,png,svg|max:10480',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $imagePath = $this->imageService->store($request->file('image'), 'images/food-menu');
        
        $foodMenu = FoodMenu::create([
            'name' => $request->food_name,
            'description' => $request->food_description,
            'price' => $request->price,
            'category_id' => $request->category_id,
            'image' => $imagePath,
        ]);

        Log::info([$foodMenu]);

        return back()->with('success-message', 'Menu added successfully.');
    }

    /*
    *  Function view show file
    */
    public function show($id) : View
    {
        $menu = FoodMenu::findOrFail($id);

        return view('company.admin.food-menu.show', ['menu' => $menu]);
    }

    
    /*
    *  Function to view edit file
    */
    public function edit($id) : View
    {
        $menu = FoodMenu::findOrFail($id);

        $categoryid = $menu->category_id;

        $menuCategory = FoodCategory::findOrFail($categoryid);

        Log::info([$menu, $menuCategory]);

        return view('company.admin.food-menu.edit', ['menu' => $menu, 'category' => $menuCategory]);
    }

    

    /*
    *  Function to update food menu resource
    */
    public function update(Request $request, $id) : RedirectResponse
    {
        $menu = FoodMenu::findOrFail($id);

        if ($request->anyFilled(['name', 'description', 'price', 'category', 'image'])) {

            $updateData = $this->getFilledData($request, [
                'name',
                'price',
                'category',
            ]);

            if ($request->filled('description')) {
                $updateData['description'] = $request->input('description') . " " . $request->input('description-two');
            }

            if ($request->hasFile('image')) {

                $imagePath = $this->imageService->replace($request->file('image'), 'images/food-menu', $menu->image);

                $updateData['image'] = $imagePath;
            }

            $updated = $menu->update($updateData);

            Log::info([$updateData, $updated]);

            return redirect()->route('food-menu-show', $menu->id)->with('success-message', 'Your changes are saved successfully.');
        }
        else {
            return back()->withErrors([
                'error-message' => 'Please insert data to update menu.' 
            ]);
        }
    }

    
    /*
    *  Function to delete food menu resource
    */
    public function destroy($id) : RedirectResponse
    {
        $menu = FoodMenu::findOrFail($id);

        $this->imageService->delete($menu->image);

        $menu->delete();

        Log::info([$menu]);

        return redirect()->route('food-menu')->with('success-message', 'Menu is deleted successfully.');
    }
}

