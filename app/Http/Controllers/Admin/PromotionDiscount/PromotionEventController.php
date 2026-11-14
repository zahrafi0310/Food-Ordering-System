<?php

namespace App\Http\Controllers\Admin\PromotionDiscount;

use App\Http\Controllers\Controller;
use App\Models\PromotionEvent;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PromotionEventController extends Controller
{
    public function __construct(private ImageService $imageService)
    {
    }

    /*
    *  Function to store data event into item promotion_events table
    */
    public function store(Request $request) : RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'required|max:255',
            'event_date' => 'required|date',
            'image' => 'required|image|mimes:jpg,jpeg,png,svg|max:10480',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Retrieve a submitted input of event_name
        $validated = $validator->safe()->only('event_name');

        // Check if the event name is exists
        $exists = PromotionEvent::where('event_name', $validated)->exists();

        if ($exists) {
            return back()->withErrors([
                'error-message' => 'Event name already exists.'
            ]);
        }
        else {
            $imagePath = $this->imageService->store($request->file('image'), 'images/promotion-event');

            $eventCreate = PromotionEvent::create([
                'event_name' => $request->event_name,
                'event_date' => $request->event_date,
                'event_image' => $imagePath,
            ]);

            Log::info([$eventCreate]);

            return back()->with('success-message', 'Event created successfully.');
        }
    }
}