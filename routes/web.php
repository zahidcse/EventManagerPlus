<?php

use App\Http\Controllers\AccountBookingTicketController;
use App\Http\Controllers\Admin\AdminBookingNotificationController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingCheckInController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\FrontendAccountController;
use App\Http\Middleware\EnsureFrontendCustomer;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\EventAssistantController;
use App\Http\Controllers\Admin\EventCategoryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\OrganizerController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportAiController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\SpeakerController;
use App\Http\Controllers\Admin\StaffUserController;
use App\Http\Controllers\EventBookingPayPalController;
use App\Http\Controllers\EventBookingRazorpayController;
use App\Http\Controllers\EventBookingSslCommerzController;
use App\Http\Controllers\EventBookingStripeController;
use App\Http\Controllers\FrontendAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Install\InstallerController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicBookingThankYouController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\PublicPageController;
use App\Http\Middleware\BlockInstallerAfterCompletion;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->name('install.')->group(function (): void {
    Route::middleware(BlockInstallerAfterCompletion::class)->group(function (): void {
        Route::get('/', [InstallerController::class, 'index'])->name('index');
        Route::get('/setup', [InstallerController::class, 'setup'])->name('setup');
        Route::get('/finish', [InstallerController::class, 'finishHelp'])->name('finish.help');
        Route::post('/finish', [InstallerController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('finish');
    });
    Route::get('/complete', [InstallerController::class, 'complete'])->name('complete');
});

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [FrontendAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [FrontendAuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [FrontendAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [FrontendAuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [FrontendAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', EnsureFrontendCustomer::class])->prefix('account')->name('account.')->group(function (): void {
    Route::get('/', [FrontendAccountController::class, 'index'])->name('index');
    Route::get('/orders/{orderGroupId}', [FrontendAccountController::class, 'order'])->name('bookings.order');
    Route::get('/bookings/{booking}/ticket', [AccountBookingTicketController::class, 'show'])->name('bookings.ticket-pdf');
    Route::get('/profile', [FrontendAccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [FrontendAccountController::class, 'updateProfile'])->name('profile.update');
});

Route::get('/events', [PublicEventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [PublicEventController::class, 'show'])->name('events.show');

Route::get('/blog', [PublicBlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{blog_post:slug}', [PublicBlogController::class, 'show'])->name('blog.show');

Route::get('/pages/{page:slug}', [PublicPageController::class, 'show'])->name('pages.show');
Route::post('/events/{event:slug}/book', [PublicEventController::class, 'book'])
    ->middleware('throttle:20,1')
    ->name('events.book');
Route::get('/events/{event:slug}/booking/thank-you', [PublicBookingThankYouController::class, 'show'])
    ->name('events.booking.thank-you');
Route::get('/events/{event:slug}/booking/tickets/{booking}/pdf', [PublicBookingThankYouController::class, 'ticketPdf'])
    ->whereNumber('booking')
    ->name('events.booking.ticket-pdf');
Route::get('/events/{event:slug}/booking/tickets/{booking}/print', [PublicBookingThankYouController::class, 'ticketPrint'])
    ->whereNumber('booking')
    ->name('events.booking.ticket-print');

Route::get('/events/booking/stripe/return', [EventBookingStripeController::class, 'return'])
    ->name('events.booking.stripe.return');
Route::get('/events/booking/paypal/return', [EventBookingPayPalController::class, 'return'])
    ->name('events.booking.paypal.return');
Route::get('/events/booking/razorpay/{checkout}', [EventBookingRazorpayController::class, 'pay'])
    ->middleware(['signed'])
    ->name('events.booking.razorpay.pay');
Route::post('/events/booking/razorpay/verify', [EventBookingRazorpayController::class, 'verify'])
    ->middleware('throttle:30,1')
    ->name('events.booking.razorpay.verify');
Route::post('/events/booking/sslcommerz/success', [EventBookingSslCommerzController::class, 'success'])
    ->middleware('throttle:40,1')
    ->name('events.booking.sslcommerz.success');
Route::post('/events/booking/sslcommerz/ipn', [EventBookingSslCommerzController::class, 'ipn'])
    ->name('events.booking.sslcommerz.ipn');
Route::match(['get', 'post'], '/events/booking/sslcommerz/fail', [EventBookingSslCommerzController::class, 'fail'])
    ->middleware('throttle:40,1')
    ->name('events.booking.sslcommerz.fail');
Route::match(['get', 'post'], '/events/booking/sslcommerz/cancel', [EventBookingSslCommerzController::class, 'cancel'])
    ->middleware('throttle:40,1')
    ->name('events.booking.sslcommerz.cancel');
Route::post('/stripe/webhook', [EventBookingStripeController::class, 'webhook'])
    ->name('stripe.webhook');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', EnsureIsAdmin::class])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::post('/notifications/bookings/{booking}/dismiss', [AdminBookingNotificationController::class, 'dismiss'])
            ->middleware('throttle:120,1')
            ->name('notifications.bookings.dismiss');

        Route::get('/check-in/{token}', [BookingCheckInController::class, 'show'])
            ->where('token', '[a-z0-9]{20,64}')
            ->name('check-in.show');
        Route::post('/check-in/{token}', [BookingCheckInController::class, 'store'])
            ->where('token', '[a-z0-9]{20,64}')
            ->name('check-in.store');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/orders', [ReportController::class, 'orders'])->name('reports.orders');
        Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');

        Route::get('/report-ai', [ReportAiController::class, 'index'])->name('report-ai.index');
        Route::post('/report-ai/query', [ReportAiController::class, 'query'])
            ->middleware('throttle:20,1')
            ->name('report-ai.query');

        Route::get('/event-assistant', [EventAssistantController::class, 'index'])->name('event-assistant.index');
        Route::post('/event-assistant/run', [EventAssistantController::class, 'run'])
            ->middleware('throttle:15,1')
            ->name('event-assistant.run');

        Route::get('/event-categories', [EventCategoryController::class, 'index'])->name('event-categories.index');
        Route::post('/event-categories', [EventCategoryController::class, 'store'])->name('event-categories.store');
        Route::get('/event-categories/{eventCategory}/edit', [EventCategoryController::class, 'edit'])->name('event-categories.edit');
        Route::put('/event-categories/{eventCategory}', [EventCategoryController::class, 'update'])->name('event-categories.update');
        Route::delete('/event-categories/{eventCategory}', [EventCategoryController::class, 'destroy'])->name('event-categories.destroy');

        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::get('/events/{event}/media', [EventController::class, 'media'])->name('events.edit.media');
        Route::put('/events/{event}/media', [EventController::class, 'updateMedia'])->name('events.update.media');
        Route::post('/events/{event}/gallery', [EventController::class, 'uploadGalleryImage'])->name('events.gallery.upload');
        Route::post('/events/{event}/hero', [EventController::class, 'uploadHeroImage'])->name('events.hero.upload');
        Route::delete('/events/{event}/gallery/{galleryImage}', [EventController::class, 'destroyGalleryImage'])->name('events.gallery.destroy');
        Route::get('/events/{event}/location', [EventController::class, 'location'])->name('events.edit.location');
        Route::put('/events/{event}/location', [EventController::class, 'updateLocation'])->name('events.update.location');
        Route::get('/events/{event}/tickets', [EventController::class, 'tickets'])->name('events.edit.tickets');
        Route::get('/events/{event}/register-attendee', [EventController::class, 'registerAttendee'])
            ->name('events.register-attendee');
        Route::post('/events/{event}/registrations', [EventController::class, 'storeRegistration'])
            ->middleware('throttle:60,1')
            ->name('events.registrations.store');
        Route::put('/events/{event}/tickets', [EventController::class, 'updateTickets'])->name('events.update.tickets');
        Route::get('/events/{event}/speakers', [EventController::class, 'speakers'])->name('events.edit.speakers');
        Route::put('/events/{event}/speakers', [EventController::class, 'updateSpeakers'])->name('events.update.speakers');
        Route::get('/events/{event}/content', [EventController::class, 'content'])->name('events.edit.content');
        Route::put('/events/{event}/content', [EventController::class, 'updateContent'])->name('events.update.content');
        Route::post('/events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
        Route::post('/events/{event}/draft', [EventController::class, 'saveDraft'])->name('events.draft');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::get('/events/{event}/bookings/data', [EventController::class, 'bookingsData'])->name('events.bookings.data');
        Route::get('/events/{event}/bookings', [EventController::class, 'bookings'])->name('events.bookings');
        Route::get('/events/{event}/bookings/{order_group_id}', [EventController::class, 'bookingDetails'])->name('events.bookings.details');
        Route::put('/events/{event}/bookings/{order_group_id}', [EventController::class, 'updateBookingGroup'])->name('events.bookings.update');

        Route::post('/editor/upload', [EditorUploadController::class, 'store'])->name('editor.upload');

        Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

        Route::get('/blog', [BlogPostController::class, 'index'])->name('blog.index');
        Route::get('/blog/create', [BlogPostController::class, 'create'])->name('blog.create');
        Route::post('/blog', [BlogPostController::class, 'store'])->name('blog.store');
        Route::get('/blog/{blog_post}/edit', [BlogPostController::class, 'edit'])->name('blog.edit');
        Route::put('/blog/{blog_post}', [BlogPostController::class, 'update'])->name('blog.update');
        Route::delete('/blog/{blog_post}', [BlogPostController::class, 'destroy'])->name('blog.destroy');

        Route::get('/organizers', [OrganizerController::class, 'index'])->name('organizers.index');
        Route::get('/organizers/create', [OrganizerController::class, 'create'])->name('organizers.create');
        Route::post('/organizers', [OrganizerController::class, 'store'])->name('organizers.store');
        Route::get('/organizers/{organizer}/edit', [OrganizerController::class, 'edit'])->name('organizers.edit');
        Route::put('/organizers/{organizer}', [OrganizerController::class, 'update'])->name('organizers.update');
        Route::delete('/organizers/{organizer}', [OrganizerController::class, 'destroy'])->name('organizers.destroy');

        Route::get('/speakers', [SpeakerController::class, 'index'])->name('speakers.index');
        Route::get('/speakers/create', [SpeakerController::class, 'create'])->name('speakers.create');
        Route::post('/speakers', [SpeakerController::class, 'store'])->name('speakers.store');
        Route::get('/speakers/{speaker}/edit', [SpeakerController::class, 'edit'])->name('speakers.edit');
        Route::put('/speakers/{speaker}', [SpeakerController::class, 'update'])->name('speakers.update');
        Route::delete('/speakers/{speaker}', [SpeakerController::class, 'destroy'])->name('speakers.destroy');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/staff', [StaffUserController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffUserController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffUserController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staffUser}/edit', [StaffUserController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staffUser}', [StaffUserController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staffUser}', [StaffUserController::class, 'destroy'])->name('staff.destroy');

        Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [AdminRoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [AdminRoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
    });
});
