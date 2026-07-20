<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BazaarBooking;
use App\Models\BazaarBookingDocument;
use App\Models\BazaarPeriod;
use App\Models\BazaarTable;
use App\Models\BazaarVendorCategory;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BazaarController extends Controller
{
    /** Session key holding the bookings made in this browser session. */
    private const SESSION_KEY = 'bazaar_bookings';

    /**
     * Certificates are scans and phone photos; 5 MB covers a camera shot.
     *
     * Keep this in step with the server: php's upload_max_filesize must exceed
     * MAX_UPLOAD_KB, and post_max_size (and nginx client_max_body_size) must
     * exceed MAX_FILES_PER_KIND * 2 * MAX_UPLOAD_KB -- otherwise PHP discards
     * the upload before validation runs and the vendor is told their
     * certificate is missing with no explanation.
     */
    private const MAX_UPLOAD_KB = 5120;

    private const MAX_FILES_PER_KIND = 4;

    private const ALLOWED_MIMES = 'pdf,jpg,jpeg,png,webp';

    public function index(Request $request)
    {
        $periods = BazaarPeriod::active()->upcoming()
            ->with('nights')
            ->orderBy('starts_on')
            ->get();

        // Default to the next weekend still to come.
        $selected = $periods->firstWhere('id', (int) $request->query('period')) ?? $periods->first();

        $tables = BazaarTable::active()->orderBy('number')->get();

        $takenIds = $selected
            ? BazaarBooking::query()
                ->where('bazaar_period_id', $selected->id)
                ->whereNot('status', BazaarBooking::STATUS_CANCELLED)
                ->pluck('bazaar_table_id')
                ->all()
            : [];

        return view('bazaar.index', [
            'periods' => $periods,
            'selectedPeriod' => $selected,
            'tables' => $tables,
            'takenIds' => $takenIds,
            'categories' => BazaarVendorCategory::active()->get(),
            'price' => BazaarTable::bookable()->value('price') ?? 30,
            'deposit' => (float) Setting::get('bazaar_deposit', '10'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bazaar_period_id' => ['required', 'exists:bazaar_periods,id'],
            'bazaar_table_id' => ['required', 'exists:bazaar_tables,id'],
            'bazaar_vendor_category_id' => ['required', 'exists:bazaar_vendor_categories,id'],
            'vendor_name' => ['required', 'string', 'max:255'],
            // Permissive on formatting, strict on having enough digits to call.
            'vendor_phone' => ['required', 'string', 'max:32', 'regex:/^[\d\s\-\+\(\)]{9,}$/'],
            'vendor_business' => ['nullable', 'string', 'max:255'],
            'goods_description' => ['nullable', 'string', 'max:2000'],
            'work_certificate' => ['nullable', 'array', 'max:'.self::MAX_FILES_PER_KIND],
            'work_certificate.*' => ['file', 'mimes:'.self::ALLOWED_MIMES, 'max:'.self::MAX_UPLOAD_KB],
            'health_certificate' => ['nullable', 'array', 'max:'.self::MAX_FILES_PER_KIND],
            'health_certificate.*' => ['file', 'mimes:'.self::ALLOWED_MIMES, 'max:'.self::MAX_UPLOAD_KB],
        ], [
            'vendor_phone.regex' => __('Please enter a valid phone number.'),
            'bazaar_vendor_category_id.required' => __('Please choose what you sell.'),
            'work_certificate.max' => __('You can attach up to :count files.', ['count' => self::MAX_FILES_PER_KIND]),
            'health_certificate.max' => __('You can attach up to :count files.', ['count' => self::MAX_FILES_PER_KIND]),
            'work_certificate.*.max' => __('Each file must be under 5 MB.'),
            'health_certificate.*.max' => __('Each file must be under 5 MB.'),
            'work_certificate.*.mimes' => __('Please upload a PDF or an image.'),
            'health_certificate.*.mimes' => __('Please upload a PDF or an image.'),
        ]);

        $period = BazaarPeriod::findOrFail($data['bazaar_period_id']);
        $table = BazaarTable::findOrFail($data['bazaar_table_id']);
        $category = BazaarVendorCategory::findOrFail($data['bazaar_vendor_category_id']);

        if (! $period->is_active || $period->hasFinished()) {
            return back()->withInput()->withErrors([
                'bazaar_period_id' => __('That weekend is no longer open for booking.'),
            ]);
        }

        if (! $table->is_bookable || ! $table->is_active) {
            return back()->withInput()->withErrors([
                'bazaar_table_id' => __('That table is not available to book.'),
            ]);
        }

        // Food, drink and anything applied to the body cannot trade without a
        // health certificate, so the booking is not accepted without one.
        if ($category->requires_health_certificate && ! $request->hasFile('health_certificate')) {
            return back()->withInput()->withErrors([
                'health_certificate' => __('A health certificate is required for :category.', [
                    'category' => $category->name,
                ]),
            ]);
        }

        // Files are stored before the booking so a storage failure can't leave a
        // paid-for booking without its paperwork; on a failed write we roll the
        // uploads back ourselves. A vendor may attach several of each kind --
        // a licence and its renewal, or a certificate per product line.
        $stored = [];

        try {
            foreach ([
                BazaarBookingDocument::KIND_WORK => $request->file('work_certificate', []),
                BazaarBookingDocument::KIND_HEALTH => $request->file('health_certificate', []),
            ] as $kind => $files) {
                foreach (Arr::wrap($files) as $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $stored[] = [
                        'kind' => $kind,
                        'path' => $file->store(BazaarBookingDocument::DIRECTORY, 'local'),
                        'original_name' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->discard($stored);
            report($e);

            return back()->withInput()->withErrors([
                'work_certificate' => __('We could not save your file. Please try again.'),
            ]);
        }

        try {
            $booking = DB::transaction(function () use ($data, $period, $table, $category, $stored) {
                // Vendors are customers too -- reuse the record by phone so a
                // vendor who also shops is not duplicated.
                $customer = Customer::firstOrCreate(
                    ['phone' => $data['vendor_phone']],
                    ['name' => $data['vendor_name']]
                );

                $booking = BazaarBooking::create([
                    'bazaar_period_id' => $period->id,
                    'bazaar_table_id' => $table->id,
                    'customer_id' => $customer->id,
                    'bazaar_vendor_category_id' => $category->id,
                    'vendor_name' => $data['vendor_name'],
                    'vendor_phone' => $data['vendor_phone'],
                    'vendor_business' => $data['vendor_business'] ?? null,
                    'goods_description' => $data['goods_description'] ?? null,
                    'price' => $table->price,
                    'deposit' => (float) Setting::get('bazaar_deposit', '10'),
                    'status' => BazaarBooking::STATUS_PENDING,
                ]);

                foreach ($stored as $file) {
                    $booking->documents()->create($file);
                }

                return $booking;
            });
        } catch (QueryException $e) {
            // The unique index caught a race: someone took this table between
            // the map being drawn and this form being submitted.
            $this->discard($stored);

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
                'description' => 'Table #'.$table->number.' booked for '.$period->starts_on->toDateString().' weekend',
                'subject_type' => $booking->getMorphClass(),
                'subject_id' => $booking->getKey(),
                'properties' => ['attributes' => [
                    'vendor_name' => $booking->vendor_name,
                    'vendor_phone' => $booking->vendor_phone,
                    'category' => $category->name_en,
                    'price' => $booking->price,
                    'deposit' => $booking->deposit,
                    'documents' => count($stored),
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

        $booking->load(['period.nights', 'table', 'category', 'documents']);

        return view('bazaar.confirmation', compact('booking'));
    }

    /**
     * Streams a certificate to a signed-in staff member. These files sit on the
     * private disk precisely so they are never served straight off the web root.
     */
    public function document(BazaarBookingDocument $document)
    {
        abort_unless(auth('web')->check(), 403);
        abort_unless($document->exists(), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    /** Remove uploads that never made it onto a booking. */
    private function discard(array $stored): void
    {
        foreach ($stored as $file) {
            try {
                Storage::disk('local')->delete($file['path']);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
