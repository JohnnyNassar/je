<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BazaarBooking;
use App\Models\BazaarNight;
use App\Models\BazaarTable;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BazaarController extends Controller
{
    /** Session key holding the bookings made in this browser session. */
    private const SESSION_KEY = 'bazaar_bookings';

    public function index(Request $request)
    {
        $nights = BazaarNight::active()->upcoming()->orderBy('event_date')->get();

        // Default to the next night still to come.
        $selected = $nights->firstWhere('id', (int) $request->query('night')) ?? $nights->first();

        $tables = BazaarTable::active()->orderBy('number')->get();

        $takenIds = $selected
            ? BazaarBooking::query()
                ->where('bazaar_night_id', $selected->id)
                ->whereNot('status', BazaarBooking::STATUS_CANCELLED)
                ->pluck('bazaar_table_id')
                ->all()
            : [];

        return view('bazaar.index', [
            'nights' => $nights,
            'selectedNight' => $selected,
            'tables' => $tables,
            'takenIds' => $takenIds,
            'price' => BazaarTable::bookable()->value('price') ?? 30,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bazaar_night_id' => ['required', 'exists:bazaar_nights,id'],
            'bazaar_table_id' => ['required', 'exists:bazaar_tables,id'],
            'vendor_name' => ['required', 'string', 'max:255'],
            // Permissive on formatting, strict on having enough digits to call.
            'vendor_phone' => ['required', 'string', 'max:32', 'regex:/^[\d\s\-\+\(\)]{9,}$/'],
            'vendor_business' => ['nullable', 'string', 'max:255'],
            'goods_description' => ['nullable', 'string', 'max:2000'],
        ], [
            'vendor_phone.regex' => __('Please enter a valid phone number.'),
        ]);

        $night = BazaarNight::findOrFail($data['bazaar_night_id']);
        $table = BazaarTable::findOrFail($data['bazaar_table_id']);

        if (! $night->is_active || $night->hasFinished()) {
            return back()->withInput()->withErrors([
                'bazaar_night_id' => __('That night is no longer open for booking.'),
            ]);
        }

        if (! $table->is_bookable || ! $table->is_active) {
            return back()->withInput()->withErrors([
                'bazaar_table_id' => __('That table is not available to book.'),
            ]);
        }

        try {
            $booking = DB::transaction(function () use ($data, $night, $table) {
                // Vendors are customers too -- reuse the record by phone so a
                // vendor who also shops is not duplicated.
                $customer = Customer::firstOrCreate(
                    ['phone' => $data['vendor_phone']],
                    ['name' => $data['vendor_name']]
                );

                return BazaarBooking::create([
                    'bazaar_night_id' => $night->id,
                    'bazaar_table_id' => $table->id,
                    'customer_id' => $customer->id,
                    'vendor_name' => $data['vendor_name'],
                    'vendor_phone' => $data['vendor_phone'],
                    'vendor_business' => $data['vendor_business'] ?? null,
                    'goods_description' => $data['goods_description'] ?? null,
                    'price' => $table->price,
                    'status' => BazaarBooking::STATUS_PENDING,
                ]);
            });
        } catch (QueryException $e) {
            // The unique index caught a race: someone took this table between
            // the map being drawn and this form being submitted.
            return back()->withInput()->withErrors([
                'bazaar_table_id' => __('Sorry, table :number was just booked by someone else. Please pick another.', [
                    'number' => $table->number,
                ]),
            ]);
        }

        // Remember it so the vendor can see their own confirmation page.
        $request->session()->push(self::SESSION_KEY, $booking->id);

        // Storefront bookings have no admin behind them, so the model trait
        // skips them -- log explicitly, and never let auditing break a booking.
        try {
            ActivityLog::create([
                'log_name' => 'BazaarBooking',
                'event' => 'booked',
                'description' => 'Table #'.$table->number.' booked for '.$night->event_date->toDateString(),
                'subject_type' => $booking->getMorphClass(),
                'subject_id' => $booking->getKey(),
                'properties' => ['attributes' => [
                    'vendor_name' => $booking->vendor_name,
                    'vendor_phone' => $booking->vendor_phone,
                    'price' => $booking->price,
                ]],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('bazaar.confirmation', $booking);
    }

    public function confirmation(Request $request, BazaarBooking $booking)
    {
        $mine = in_array($booking->id, $request->session()->get(self::SESSION_KEY, []), true);

        // Staff can open any booking; a vendor only their own from this session.
        abort_unless($mine || auth('web')->check(), 404);

        $booking->load(['night', 'table']);

        return view('bazaar.confirmation', compact('booking'));
    }
}
