<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReservationStatusEnum;
use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Models\FoodCategory;
use App\Models\FoodMenu;
use App\Models\PromotionDiscount;
use App\Models\PromotionEvent;
use App\Models\Reservation;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PublicController extends Controller
{
    private const PROMOTION_MIN_PRICE = 27.00;

    public function __construct(private OrderService $orderService)
    {
    }

    public function home() : View
    {
        $menu = FoodMenu::all();

        return view('public.home', compact('menu'));
    }

    /*
    *  Function to view menu file
    */
    public function menu() : View
    {
        $menu = FoodMenu::all();

        $category = FoodCategory::all();

        return view('public.menu', compact('menu', 'category'));
    }

    /*
    *  Function to view about file
    */
    public function about() : View
    {
        return view('public.about');
    }

    /*
    *  Function to view promotion file
    */
    public function promotion() : View
    {
        // Get current date
        $currentMonth = Carbon::now();

        // Get month before now
        $monthBefore = Carbon::now()->subMonth();

        // Get event available if user is within the event month and one month after even
        // Other than that the promotion is unavailable
        $promotion = PromotionEvent::where(function ($query) use ($currentMonth, $monthBefore) {
            $query->whereMonth('event_date', '=', $currentMonth)
                ->orWhereMonth('event_date', '=', $monthBefore);
        })->get();

        // Initialize as empty array to store coupon based on $promotion
        $coupon = [];

        foreach ($promotion as $event) {
            $eventCoupon = PromotionDiscount::where('event_id', $event->id)->get();
            $coupon[$event->id] = $eventCoupon;
        }

        $menu = FoodMenu::where('price', '>', self::PROMOTION_MIN_PRICE)->get();

        return view('public.promotion', compact('promotion', 'coupon', 'menu'));
    }

    /*
    *  Function to view reservation file
    */
    public function reservation() : View
    {
        return view('public.reservation');
    }

    /*
    *  Function to search
    */
    public function search(Request $request) : View
    {
        $keyword = $request->input('search');

        $search = $this->applyLikeSearch(
            FoodMenu::query(),
            $keyword,
            [
                'name',
                'price',
            ]
        );

        $search->orWhereHas('foodCategory', function ($query) use ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        });

        $search = $search->get();

        Log::info([$keyword, $search]);

        return view('public.menu', ['menu' => $search]);
    }

    /*
    *  Function for add to cart
    */
    public function createOrder(Request $request) : JsonResponse
    {
        $contact = $request->filled('customer_contact')
            ? $request->input('customer_contact')
            : null;

        try {
            $this->orderService->createOrder(
                $request->input('table_number'),
                $request->input('cartData', []),
                $request->input('totalAmount'),
                $contact
            );
        } catch (OrderException $e) {
            return response()->json([
                'validation-error-message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success-message' => 'Your order is being processed. Please wait 15 - 30 minutes for us to prepare your food.',
        ]);
    }

    /*
    *  Function for reservation
    */
    public function makeReservation(Request $request) : RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'book_name' => 'required|max:255',
            'book_email' => 'required|email',
            'book_phone' => 'required|numeric',
            'guest_number' => 'required|numeric|min:2',
            'book_date' => 'required|date|after:today',
            'book_time' => 'required|date_format:H:i',
            'book_message' => 'max:999999'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Store reservation data
        $reservation = Reservation::create([
            'reservation_name' => $request->book_name,
            'reservation_email' => $request->book_email,
            'reservation_contact' => $request->book_phone,
            'reservation_attendees' => $request->guest_number,
            'reservation_date' => $request->book_date,
            'reservation_time' => $request->book_time,
            'reservation_message' => $request->book_message,
            'dining_table_id' => null,
            'reservation_status' => ReservationStatusEnum::Pending,
        ]);

        Log::info($reservation);

        return back()->with('success-message', 'We have received your reservation. We will process immediately and we will contact you as soon as possible. Thank you.');
    }
}