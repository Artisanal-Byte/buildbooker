<?php

use App\Models\Customer;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $organisation = Organisation::create([
        'name' => 'Build Booker Org',
    ]);

    $user = User::factory()->create([
        'organisation_id' => $organisation->id,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('dashboard outstanding amount excludes soft deleted transactions', function () {
    $organisation = Organisation::create([
        'name' => 'Build Booker Org',
    ]);

    $user = User::factory()->create([
        'organisation_id' => $organisation->id,
    ]);

    $project = Project::create([
        'organisation_id' => $organisation->id,
        'name' => 'Alpha Project',
        'email' => 'alpha@example.com',
        'logo' => 'logos/alpha.png',
        'jurisdiction' => 'Surat',
        'office_display_address' => 'Office Address',
        'site_display_address' => 'Site Address',
        'total_units' => 1,
    ]);

    $customer = Customer::create([
        'project_id' => $project->id,
        'name' => 'Ravi Patel',
        'mobile' => '9876543210',
        'email' => 'ravi@example.com',
        'address' => 'Test Address',
    ]);

    $unit = Unit::create([
        'project_id' => $project->id,
        'customer_id' => $customer->id,
        'type' => 'shop',
        'unit_no' => 'A-1',
        'is_sold' => true,
        'base_amount' => 850,
        'gst_amount' => 150,
        'total_amount' => 1000,
    ]);

    Transaction::create([
        'project_id' => $project->id,
        'customer_id' => $customer->id,
        'unit_id' => $unit->id,
        'receipt_number' => '#00001',
        'receipt_date' => '2026-07-01',
        'payment_date' => '2026-07-01',
        'unit_no' => $unit->unit_no,
        'bank_name' => 'Axis Bank',
        'bank_branch' => 'Ring Road',
        'payment_type' => 'cash',
        'payment_reference' => 'REF-1',
        'transaction_amount' => 300,
        'gst' => false,
    ]);

    $transactionToDelete = Transaction::create([
        'project_id' => $project->id,
        'customer_id' => $customer->id,
        'unit_id' => $unit->id,
        'receipt_number' => '#00002',
        'receipt_date' => '2026-07-02',
        'payment_date' => '2026-07-02',
        'unit_no' => $unit->unit_no,
        'bank_name' => 'Axis Bank',
        'bank_branch' => 'Ring Road',
        'payment_type' => 'cash',
        'payment_reference' => 'REF-2',
        'transaction_amount' => 100,
        'gst' => false,
    ]);

    $this->actingAs($user);

    $this->get(route('delete-transaction', $transactionToDelete))
        ->assertRedirect();

    $this->assertSoftDeleted('transactions', [
        'id' => $transactionToDelete->id,
    ]);

    $this->get(route('dashboard'))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('totalOutStandingAmmount', 700)
            ->where('projects.0.totalPendingAmountOfProject', 700)
        );
});
