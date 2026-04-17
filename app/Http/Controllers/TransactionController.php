<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreRequest;
use App\Models\Customer;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\Unit;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    // 🔍 List all transactions
    public function index(Organisation $organisation, $id = null)
    {
        $project = Project::find($id);

        if ($project == null) {
            // Get all transactions for the organisation with customer and unit
            $transactions = $organisation->transactions()->with(['customer', 'unit', 'project'])->withTrashed()->orderBy("payment_date", 'desc')->get();
            return Inertia::render('transactions/Index', [
                'transactions' => $transactions,
                'project' => [],
                'organisation' => $organisation,
                'isProject' => false
            ]);
        }

        $transactions = Transaction::with(['customer', 'unit'])->whereHas('unit', function ($q) use ($id) {
            $q->where('project_id', $id);
        })->withTrashed()->get();
        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
            'project' => $project,
            'organisation' => $organisation,
            'isProject' => true
        ]);
    }

    // ➕ Show create form
    public function create(Unit $unit = null, Project $project = null)
    {
        $units = $project ? $project->units : [];

        if ($project) {
            return Inertia::render('transactions/Create', [
                'unit' => $unit,
                'units' => $units,
                'project' => $project,
            ]);
        }

        abort(404);
    }

    public function store(TransactionStoreRequest $request, Unit $unit = null)
    {
        try {
            DB::beginTransaction();
            if (!$unit || !$unit->customer_id) {
                throw new Exception("Invalid unit or missing customer.");
            }

            $organisation = Organisation::find(Auth::user()->organisation_id);

            $isSeparateGstSequenceEnabled = (bool) ($organisation->seperate_sequence_for_gst ?? false);

            if ($isSeparateGstSequenceEnabled) {
                $baseTransactionCount = Transaction::withTrashed()
                    ->where('project_id', $unit->project_id)
                    ->where('receipt_number', 'NOT LIKE', '#G%')
                    ->count();

                $gstTransactionCount = Transaction::withTrashed()
                    ->where('project_id', $unit->project_id)
                    ->where('receipt_number', 'LIKE', '#G%')
                    ->count();

                $receiptNumber = $request->boolean('gst')
                    ? '#G' . str_pad((string) ($gstTransactionCount + 1), 5, '0', STR_PAD_LEFT)
                    : '#' . str_pad((string) ($baseTransactionCount + 1), 5, '0', STR_PAD_LEFT);
            } else {
                $transactionCount = Transaction::withTrashed()
                    ->where('project_id', $unit->project_id)
                    ->count();

                $receiptNumber = $request->boolean('gst')
                    ? '#G' . str_pad((string) ($transactionCount + 1), 5, '0', STR_PAD_LEFT)
                    : '#' . str_pad((string) ($transactionCount + 1), 5, '0', STR_PAD_LEFT);
            }

            $receiptNumber = $this->resolveUniqueReceiptNumber(
                $unit->project_id,
                $receiptNumber,
                (bool) $request->boolean('gst')
            );

            Transaction::create([
                'customer_id' => $unit->customer_id,
                'unit_id' => $unit->id,
                'project_id' => $unit->project_id,
                'receipt_number' => $receiptNumber,
                'receipt_date' => $request->receipt_date,
                'payment_date' => $request->payment_date,
                'unit_no' => $unit->unit_no,
                'bank_name' => $request->bank_name,
                'bank_branch' => $request->bank_branch,
                'payment_type' => $request->payment_type,
                'payment_reference' => $request->payment_reference,
                'transaction_amount' => $request->transaction_amount,
                'gst' => $request->gst,
            ]);

            DB::commit();

            ToastMagic::success('Transaction added successfully.');
            return redirect()->route('transactions.index', [
                'organisation' => Auth::user()->organisation_id,
                'project' => $unit->project_id,
            ])->with('success', 'Transaction added successfully!');
        } catch (QueryException $e) {
            DB::rollBack();

            if ($e->getCode() === '23000') {
                return redirect()->back()->with('error', 'Receipt number duplicate found. Please try again.');
            }

            return redirect()->back()->with('error', 'Failed to create transaction: ' . $e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }

    public function edit(Transaction $transaction, Unit $unit = null, Project $project = null)
    {
        $findProjects = Organisation::find(Auth::user()->organisation_id)->projects->pluck('id')->toArray();
        $allCustomers = Customer::whereIn('project_id', $findProjects)->get();

        if ($unit) {
            return Inertia::render('transactions/Edit', [
                'unit' => $unit,
                'units' => null,
                'project' => $project,
                'customer' => $unit->customer,
                'allCustomers' => $allCustomers,
                'transaction' => $transaction,
            ]);
        }

        if ($project) {
            $units = $project->units()->pluck('unit_no', 'id');
            return Inertia::render('transactions/Edit', [
                'unit' => null,
                'units' => $units,
                'project' => $project,
                'customer' => null,
                'allCustomers' => $allCustomers,
                'transaction' => $transaction,
            ]);
        }

        abort(404);
    }


    public function update(TransactionStoreRequest $request, Transaction $transaction)
    {
        try {
            DB::beginTransaction();

            $unit = $transaction->unit;

            if (!$unit || !$unit->customer_id) {
                throw new Exception("Unit or customer not found.");
            }

            $transaction->update([
                "customer_id" => $unit->customer_id,
                "unit_id" => $unit->id,
                "receipt_date" => $request->receipt_date,
                "payment_date" => $request->payment_date,
                "unit_no" => $unit->unit_no,
                "bank_name" => $request->bank_name,
                "bank_branch" => $request->bank_branch,
                "payment_type" => $request->payment_type,
                "payment_reference" => $request->payment_reference,
                "transaction_amount" => $request->transaction_amount,
                "gst" => $request->gst,
            ]);

            DB::commit();

            return redirect()->route('transactions.index', [
                'organisation' => Auth::user()->organisation_id,
                'project' => $unit->project_id,
            ])->with('success', 'Transaction updated successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update transaction: ' . $e->getMessage());
        }
    }

    public function deleteTransaction(Transaction $transaction)
    {
        $totalTransactions = $transaction->unit->transactions->whereNull('deleted_at')->count();
        if ($totalTransactions === 1) {
            $this->unBook($transaction->unit);
            // $this->unBook($transaction->unit);
            return redirect()->back()->with('success', 'Transaction deleted successfully!');
        } else {
            try {
                DB::beginTransaction();
                $transaction->delete(); // Soft delete
                DB::commit();
                ToastMagic::success('Transaction deleted successfully.');
                return redirect()->back()->with('success', 'Transaction deleted successfully!');
            } catch (Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'An error occurred while deleting the current transaction details.');
            }
        }
    }

    public function unBook(Unit $unit)
    {
        try {
            DB::beginTransaction();

            $unit->customer->transactions()->delete();
            $unit->customer()->dissociate();
            $unit->base_amount = null;
            $unit->gst_amount = null;
            $unit->total_amount = null;
            $unit->is_sold = false;
            $unit->save();

            DB::commit();
            ToastMagic::success('Unit unbooked successfully.');
            return redirect()->back()->with('success', 'Transaction deleted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to unbook unit: ' . $e->getMessage());
        }
    }

    private function resolveUniqueReceiptNumber(int $projectId, string $receiptNumber, bool $isGst): string
    {
        while ($this->receiptNumberExists($projectId, $receiptNumber)) {
            $receiptNumber = $this->incrementReceiptNumber($receiptNumber, $isGst);
        }

        return $receiptNumber;
    }

    private function receiptNumberExists(int $projectId, string $receiptNumber): bool
    {
        return Transaction::withTrashed()
            ->where('project_id', $projectId)
            ->where('receipt_number', $receiptNumber)
            ->exists();
    }

    private function incrementReceiptNumber(string $receiptNumber, bool $isGst): string
    {
        $number = (int) preg_replace('/\D/', '', $receiptNumber);
        $prefix = $isGst ? '#G' : '#';

        return $prefix . str_pad((string) ($number + 1), 5, '0', STR_PAD_LEFT);
    }
}
