<?php

namespace App\Http\Controllers;

use App\Models\Snapshot;
use Illuminate\Http\Request;

class SnapshotController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->perPage ?? 10;
        $orderBy = $request->orderBy ?? 'created_at';
        $order = $request->order ?? 'DESC';

        $snapshots = Snapshot::orderBy($orderBy, $order);
        $snapshots = $snapshots->paginate($perPage);

        return response()->json($snapshots, 200);
    }

    public function get(Snapshot $snapshot)
    {
        return $snapshot;
    }

    public function store(Request $request)
    {
        $request->validate([
            'balance' => 'required|numeric|min:0',
            'note' => 'string|nullable'
        ]);

        $snapshot = Snapshot::create($request->all());

        return response()->json($snapshot, 201);
    }

    public function update(Request $request, Snapshot $snapshot)
    {
        $snapshot->update($request->all());

        return response()->json($snapshot, 200);
    }

    public function delete(Snapshot $snapshot)
    {
        $snapshot->delete();

        return response()->json(null, 204);
    }

    public function latest()
    {
        $snapshot = Snapshot::orderBy('created_at', 'DESC')->first();

        return response()->json($snapshot, 200);
    }
}
