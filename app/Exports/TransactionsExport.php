<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;

class TransactionsExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $headers = collect([[
            'created_at' => 'Дата',
            'sum' => 'Сумма',
            'category' => 'Категория',
            'note' => 'Комментарий',
            'is_marked' => 'Отметка'
        ]]);

        $data = Transaction::select(['id', 'sum', 'note', 'created_at', 'category_id', 'is_marked'])
            ->with(['category' => function ($query) {
                $query->select('id', 'name');
            }])
            ->get()
            ->map(function ($item) {
                return [
                    'created_at' => $item->created_at,
                    'sum' => $item->sum,
                    'category' => $item->category->name,
                    'note' => $item->note,
                    'is_marked' => $item->is_marked
                ];
            });

        $dataWithHeaders = $headers->merge($data);

        return $dataWithHeaders;
    }
}
