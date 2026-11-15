<?php

use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Filament\Exports\BookingExporter;
use App\Filament\Exports\ContactExporter;
use App\Filament\Exports\LeadExporter;
use App\Filament\Exports\PaymentExporter;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Support\ExportActions;
use App\Models\User;
use App\Support\Permissions;
use Database\Factories\BookingFactory;
use Database\Factories\ContactFactory;
use Database\Factories\LeadFactory;
use Database\Factories\PaymentFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Testing\TestAction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->exports = ExportAction::fake();
});

/** @return array<int, string> */
function exportHeaders(string $exporter): array
{
    return collect($exporter::getColumns())
        ->filter(fn (ExportColumn $column): bool => $column->isEnabledByDefault())
        ->map(fn (ExportColumn $column): string => $column->getLabel())
        ->values()
        ->all();
}

it('exports the filtered leads table', function () {
    LeadFactory::new()->status(LeadStatus::Quoted)->count(2)->create();
    LeadFactory::new()->status(LeadStatus::New)->create();
    $this->actingAs($this->admin);

    Livewire::test(ListLeads::class)
        ->filterTable('status', [LeadStatus::Quoted->value])
        ->callAction(TestAction::make('export')->table())
        ->assertHasNoActionErrors();

    $this->exports->assertDispatched(LeadExporter::class, fn ($export, Builder $query) => $query->count() === 2);
});

it('only exports the leads a sales user can see', function () {
    $sales = User::factory()->sales()->create();
    LeadFactory::new()->ownedBy($sales)->create();
    LeadFactory::new()->create();
    LeadFactory::new()->ownedBy($this->admin)->create();
    $this->actingAs($sales);

    Livewire::test(ListLeads::class)
        ->callAction(TestAction::make('export')->table())
        ->assertHasNoActionErrors();

    $this->exports->assertDispatched(LeadExporter::class, fn ($export, Builder $query) => $query->count() === 2);
});

it('exports only the selected contacts', function () {
    $contacts = ContactFactory::new()->count(3)->create();
    $this->actingAs($this->admin);

    Livewire::test(ListContacts::class)
        ->selectTableRecords($contacts->take(2))
        ->callAction(TestAction::make('export')->table()->bulk())
        ->assertHasNoActionErrors();

    $this->exports->assertDispatched(ContactExporter::class, fn ($export, $query, $columnMap, $options, $formats, ?array $records) => count($records ?? []) === 2);
});

it('lets payment managers export payments', function () {
    PaymentFactory::new()->create();
    $this->actingAs(User::factory()->sales()->create());

    Livewire::test(ListPayments::class)
        ->callAction(TestAction::make('export')->table())
        ->assertHasNoActionErrors();

    $this->exports->assertDispatched(PaymentExporter::class);
});

it('keeps payment exports from CRM staff without payments access', function () {
    Role::create(['name' => 'viewer'])->givePermissionTo(Permissions::CRM_VIEW);
    $viewer = User::factory()->role('viewer')->create();
    $this->actingAs($viewer);

    Livewire::test(ListLeads::class)->assertActionVisible(TestAction::make('export')->table());

    expect(ExportActions::canExport(LeadExporter::class))->toBeTrue()
        ->and(ExportActions::canExport(PaymentExporter::class))->toBeFalse();
    $this->get(route('filament.admin.resources.payments.index'))->assertForbidden();
});

it('writes readable lead rows and neutralises spreadsheet formulas', function () {
    $owner = User::factory()->sales()->create(['name' => 'Pema Sherpa']);
    $contact = ContactFactory::new()->create(['first_name' => '=HYPERLINK("x")', 'last_name' => 'Silva', 'email' => 'ana@example.com', 'phone' => '+977-01-5928066', 'country' => 'Portugal']);
    $lead = LeadFactory::new()->forTrip(TripFactory::new()->create(['title' => 'Everest Base Camp']))->ownedBy($owner)->status(LeadStatus::DepositPaid)->create([
        'contact_id' => $contact->id, 'adults' => 2, 'children' => 1, 'start_date' => '2027-04-10', 'estimated_value' => 4500, 'source' => 'google',
    ]);

    $row = array_combine(exportHeaders(LeadExporter::class), LeadExporter::test()->export($lead->fresh()));

    expect($row)
        ->toMatchArray([
            'Reference' => $lead->reference,
            'Status' => 'Deposit paid',
            'Contact name' => "'=HYPERLINK(\"x\") Silva",
            'Email' => 'ana@example.com',
            'Phone' => '+977-01-5928066',
            'Country' => 'Portugal',
            'Trip' => 'Everest Base Camp',
            'Travellers' => '3',
            'Start date' => '2027-04-10',
            'Estimated value' => '4500.00',
            'Source' => 'google',
            'Owner' => 'Pema Sherpa',
        ])
        ->and($row['Received'])->toBe($lead->created_at->format('Y-m-d H:i'));
});

it('exports bookings and payments without private fields', function () {
    $booking = BookingFactory::new()->create(['total' => 3000, 'amount_paid' => 600, 'emergency_contact' => 'Secret Person +1 555', 'internal_notes' => 'Internal only']);
    $payment = PaymentFactory::new()->forBooking($booking)->paid()->create(['amount' => 600, 'gateway_reference' => 'PP-123', 'gateway_payload' => ['token' => 'secret-token']]);

    $bookingRow = array_combine(exportHeaders(BookingExporter::class), BookingExporter::test()->export($booking->fresh()));
    $paymentRow = array_combine(exportHeaders(PaymentExporter::class), PaymentExporter::test()->export($payment->fresh()));

    expect($bookingRow)
        ->toMatchArray(['Booking' => $booking->number, 'Total' => '3000.00', 'Paid' => '600.00', 'Balance due' => '2400'])
        ->and(implode('|', $bookingRow))->not->toContain('Secret Person')->not->toContain('Internal only')
        ->and($paymentRow)->toMatchArray([
            'Payment ID' => $payment->uuid,
            'For' => 'Booking '.$booking->number,
            'Amount' => '600.00',
            'Status' => PaymentStatus::Paid->getLabel(),
            'Gateway' => 'manual',
            'Reference' => 'PP-123',
        ])
        ->and(implode('|', $paymentRow))->not->toContain('secret-token');
});

it('removes exports and their files after 7 days', function () {
    $old = Export::create(['exporter' => LeadExporter::class, 'file_disk' => 'local', 'total_rows' => 0, 'user_id' => $this->admin->id ?? User::factory()->create()->id]);
    $old->forceFill(['created_at' => now()->subDays(8)])->save();
    $recent = Export::create(['exporter' => LeadExporter::class, 'file_disk' => 'local', 'total_rows' => 0, 'user_id' => $old->user_id]);

    $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'exports:clean');
    $event->run(app());

    expect(Export::pluck('id')->all())->toBe([$recent->id]);
});
