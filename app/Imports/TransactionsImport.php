<?php

namespace App\Imports;

use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;

class TransactionsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Transaction([
            'sum' => $row[1],
            'note' => $row[3],
            'category_id' => $row[2],
            'user_id' => 3,
            'is_marked' => false,
            'created_at' => Carbon::parse($row[0])
        ]);
    }
}
