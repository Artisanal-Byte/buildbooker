<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Unit;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Intervention\Image\Origin;

class CustomerController extends Controller
{
    public function index(Project $project)
    {
        $project->load('customers.units.transactions');

        return Inertia::render('customers/Index', [
            'project' => $project,
            'customers' => $project->customers,
        ]);
    }

    public function getOrgCustomers(Organisation $organisation)
    {
        // dd($organisation); 

        $organisation->load('projects.customers.units.transactions');

        $customers = $organisation->projects
            ->pluck('customers')
            ->flatten()

            ->unique('id')
            ->values();
        // dd($customers);
        return Inertia::render('customers/Index', [
            'project' => null,
            'organisation' => $organisation,
            'customers' => $customers
        ]);
    }

    public function create(Project $project)
    {
        return Inertia::render('customers/Create', [
            'project' => $project,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'mobile' => ['required', 'digits:10', Rule::unique('customers', 'mobile')],
            'address' => 'nullable|string',
            'project_id' => 'required|exists:projects,id'
        ]);
        //dd($validated);

        //create a new customer instance
        $customer = new Customer($validated);
        //$customer->project_id = $project->id;
        $customer->save();
        //dd($customer);
        ToastMagic::success('Customer Add submitted successfully');
        return redirect()->route('customers.index', $project->id)->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        return Inertia::render('customers/Show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('customers/Edit', [
            'customer' => $customer,
            'projectId' => $customer->project_id,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
            'mobile' => ['required', 'digits:10', Rule::unique('customers', 'mobile')->ignore($customer->id)],
            'address' => 'nullable|string|max:500',
        ]);
        
        // dd('test');;/s
        $customer->update($validated);

        return redirect()->route('customers.index', $customer->project_id)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->units()->count() > 1) {
            return back()->with('error', 'Customer has multiple units and cannot be deleted.');
        }

        if ($customer->transactions()->exists()) {
            $customer->transactions()->delete();
        }

        $unit = Unit::where('customer_id', $customer->id)->first();
        if ($unit) {
            $unit->update([
                'customer_id' => null,
                'is_sold' => false,
                'base_amount' => null,
                'gst_amount' => null,
                'total_amount' => null,
            ]);
        }
        $customer->delete();
        $projectId = $customer->project_id;

        ToastMagic::success('Customer deleted successfully.');
        return redirect()->route('customers.index', $projectId)->with('success', 'Customer deleted.');
    }
}
