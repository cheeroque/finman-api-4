<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Exports\TransactionsExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->perPage ?? 50;
        $orderBy = $request->orderBy ?? 'created_at';
        $order = $request->order ?? 'DESC';

        $transactions = Transaction::with('category')->orderBy($orderBy, $order);

        if (isset($request->filter)) {
            $searchQuery = "%{$request->filter}%";

            $transactions = $transactions->whereHas('category', function ($query) use ($searchQuery) {
                $query->whereLike('name', $searchQuery);
            })->orWhereLike('note', $searchQuery);
        }

        if (isset($request->show)) {
            $showIncome = $request->show === 'income' ? true : false;

            $transactions = $transactions->whereHas('category', function ($query) use ($showIncome) {
                $query->where('is_income', $showIncome);
            });
        }

        if (isset($request->marked)) {
            $transactions = $transactions->where('is_marked', $request->marked === 'true');
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
        $transaction->update($request->only('note', 'sum', 'is_marked', 'category_id', 'created_at'));

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
            ->orderBy('sum', 'DESC')
            ->get();
        $transactions = $transactions->groupBy('category_id');

        return response()->json($transactions, 200);
    }

    public function getByCategory(Request $request, string $slug)
    {
        $page = $request->page ?? 1;
        $perPage = $request->perPage ?? 12;

        $category = Category::where('slug', $slug)->firstOrFail();

        $transactions = Transaction::where('category_id', $category->id)
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

    public function getCurrentMonth()
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = $endDate->copy()->startOfMonth();

        $transactions = $this->getSubtotalsByMonth($startDate, $endDate);

        return response()->json($transactions, 200);
    }

    public function getMonthly(Request $request)
    {
        $endDate = Carbon::now()->endOfMonth();
        if (isset($request->to)) {
            $endDate = Carbon::parse($request->to);
        }

        $startDate = $endDate->copy()->startOfMonth()->subMonths(11);
        if (isset($request->from)) {
            $startDate = Carbon::parse($request->from);
        }

        $transactions = $this->getSubtotalsByMonth($startDate, $endDate);

        return response()->json($transactions, 200);
    }

    public function export()
    {
        $datetime = Carbon::now()->format('d-m-Y_H-i-s');
        $filepath = "export/transactions-{$datetime}.xlsx";

        Excel::store(new TransactionsExport(), $filepath, 'public');

        $response = Storage::url($filepath);

        return $response;
    }

    private function getSubtotalsByMonth(Carbon $startDate, Carbon $endDate)
    {
        $transactions = Transaction::with('category')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('*', \DB::raw("to_char(created_at, 'YYYY-MM') period"))
            ->orderBy('period', 'DESC')
            ->get();

        $transactions = $transactions->groupBy('period')->map(function ($group) {
            $expenses = 0;
            $incomes = 0;

            $group->each(function ($item) use (&$expenses, &$incomes) {
                if ($item->category->is_income) {
                    $incomes = $incomes + $item->sum;
                } else {
                    $expenses = $expenses + $item->sum;
                }
            });

            return [
                'expenses' => $expenses,
                'incomes' => $incomes
            ];
        });

        return $transactions;
    }
}
