<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('report.transaction.index', [
            'payment_methods' => PaymentMethod::orderBy('name', 'asc')->get(),
            'transactions' => Transaction::orderBy('updated_at', 'desc')->get(),
            'type' => 'show'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): View
    {
        $transaction_details = $transaction->transactionDetails()->get();

        return view('report.transaction.show', [
            'transaction_details' => $transaction_details
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function filter(Request $request): View
    {
        $transactions = Transaction::whereDate('updated_at', '>=', $request->start_date)
            ->whereDate('updated_at', '<=', $request->end_date);

        if ($request->payment_method != 'all') {
            $transactions = $transactions->where('payment_method_id', $request->payment_method);
        }

        if ($request->status != 'all') {
            $transactions = $transactions->where('status', $request->status);
        }

        $transactions = $transactions->orderBy('updated_at', 'desc')->get();

        return view('report.transaction.table', [
            'transactions' => $transactions,
            'type' => 'filter'
        ]);
    }
}
