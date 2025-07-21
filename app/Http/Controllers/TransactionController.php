<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->perPage ?? 10;
        $orderBy = $request->orderBy ?? 'created_at';
        $order = $request->order ?? 'DESC';

        $transactions = Transaction::with('category')->orderBy($orderBy, $order);

        if (isset($request->show)) {
            $showIncome = $request->show === 'income' ? true : false;

            $transactions = $transactions->whereHas('category', function ($query) use ($showIncome) {
                $query->where('is_income', $showIncome);
            });
        }
        $transactions = $transactions->paginate($perPage);

        return response()->json($transactions, 200);
    }

    public function get($transactionId)
    {
        $transaction = Transaction::with('category')->find($transactionId);

        return response()->json($transaction, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'note' => 'required|string',
            'sum' => 'required|numeric|min:0',
            'category_id' => 'required|numeric|exists:categories,id'
        ]);

        $transaction = Transaction::create(array_merge(
            $request->only('note', 'sum', 'category_id', 'created_at'),
            ['user_id' => auth('sanctum')->user()->id]
        ));

        return response()->json($transaction, 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $transaction->update($request->only('note', 'sum', 'category_id', 'created_at'));

        return response()->json($transaction, 200);
    }

    public function delete(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json(null, 204);
    }

    public function getByMonth($year, $month)
    {
        $transactions = Transaction::with('category')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'DESC')
            ->get();
        $transactions = $transactions->groupBy('category_id');

        return response()->json($transactions, 200);
    }

    public function getByCategory(Request $request, $categoryId)
    {
        $page = $request->page ?? 1;
        $perPage = $request->perPage ?? 12;

        $category = Category::find($categoryId)->first();

        $transactions = Transaction::where('category_id', $categoryId)
            ->select('*', \DB::raw("to_char(created_at, 'YYYY-MM') period"))
            ->orderBy('period', 'DESC')
            ->get();
        $transactions = $transactions->groupBy('period');
        $total = $transactions->count();

        $transactionsPage = $transactions->forPage($page, $perPage);

        return response()->json([
            'category' => $category,
            'data' => $transactionsPage,
            'total' => $total
        ], 200);
    }

    public function total()
    {
        $expensesTotal = Transaction::whereHas('category', function ($query) {
            $query->where('is_income', false);
        })->sum('sum');

        $incomesTotal = Transaction::whereHas('category', function ($query) {
            $query->where('is_income', true);
        })->sum('sum');

        $total = $incomesTotal - $expensesTotal;

        return response()->json($total, 200);
    }

    public function first()
    {
        $transaction = Transaction::orderBy('created_at', 'ASC')->first();

        return response()->json($transaction, 200);
    }

    public function search(Request $request)
    {
        $searchQuery = "%{$request->q}%";
        $perPage = $request->perPage ?? 50;

        $transactions = Transaction::with('category')
            ->whereHas('category', function ($query) use ($searchQuery) {
                $query->whereLike('name', $searchQuery);
            })
            ->orWhereLike('note', $searchQuery)
            ->orderBy('created_at', 'DESC');
        $transactions = $transactions->paginate($perPage);

        return response()->json($transactions, 200);
    }

    public function monthly()
    {
        $expenses = Transaction::whereMonth('created_at', '=', Carbon::now()->month)
            ->whereYear('created_at', '=', Carbon::now()->year)
            ->whereHas('category', function ($query) {
                $query->where('is_income', false);
            })
            ->sum('sum');

        $incomes = Transaction::whereMonth('created_at', '=', Carbon::now()->month)
            ->whereYear('created_at', '=', Carbon::now()->year)
            ->whereHas('category', function ($query) {
                $query->where('is_income', true);
            })
            ->sum('sum');

        $monthly = [
            'expenses' => $expenses,
            'incomes' => $incomes
        ];

        return response()->json($monthly, 200);
    }
}
