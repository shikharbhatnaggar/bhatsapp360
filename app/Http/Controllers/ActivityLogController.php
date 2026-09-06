<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->string('event')->toString(), fn ($q, $e) => $q->where('event', 'like', $e.'%'))
            ->when($request->date('from'), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when($request->date('to'), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()))
            ->when($request->string('q')->toString(), fn ($q, $t) => $q->where('description', 'like', "%{$t}%"))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $groups = ActivityLog::query()
            ->selectRaw('substring_index(event, ".", 1) as grp, count(*) as total')
            ->groupBy('grp')->orderByDesc('total')->pluck('total', 'grp');

        return view('logs.index', compact('logs', 'groups'));
    }
}
