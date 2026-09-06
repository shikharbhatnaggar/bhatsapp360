<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $to = $request->date('to') ?: now()->endOfDay();
        $from = $request->date('from') ?: now()->subDays(29)->startOfDay();
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        $outbound = Message::query()->where('direction', 'outbound')->whereBetween('created_at', [$from, $to]);

        $byStatus = (clone $outbound)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $sent = (int) ($byStatus['sent'] ?? 0) + (int) ($byStatus['delivered'] ?? 0) + (int) ($byStatus['read'] ?? 0);
        $delivered = (int) ($byStatus['delivered'] ?? 0) + (int) ($byStatus['read'] ?? 0);
        $read = (int) ($byStatus['read'] ?? 0);
        $failed = (int) ($byStatus['failed'] ?? 0);
        $queued = (int) ($byStatus['queued'] ?? 0);

        $inbound = Message::query()->where('direction', 'inbound')->whereBetween('created_at', [$from, $to])->count();

        $spend = (float) (clone $outbound)->sum('price');

        $daily = (clone $outbound)
            ->selectRaw('DATE(created_at) as day, count(*) as total, sum(status in ("delivered","read")) as delivered, sum(status = "failed") as failed')
            ->groupBy('day')->orderBy('day')->get();

        $inboundDaily = Message::query()->where('direction', 'inbound')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')->pluck('total', 'day');

        $byCategory = (clone $outbound)
            ->selectRaw('pricing_category, count(*) as total, sum(price) as spend')
            ->groupBy('pricing_category')->get();

        $topCampaigns = Campaign::query()
            ->whereBetween('created_at', [$from, $to])
            ->withCount([
                'messages as delivered_count' => fn ($q) => $q->whereIn('status', ['delivered', 'read']),
                'messages as failed_count' => fn ($q) => $q->where('status', 'failed'),
            ])
            ->latest()->limit(6)->get();

        return view('dashboard.index', [
            'from' => $from,
            'to' => $to,
            'stats' => [
                'sent' => $sent,
                'delivered' => $delivered,
                'read' => $read,
                'failed' => $failed,
                'queued' => $queued,
                'replies' => $inbound,
                'spend' => $spend,
                'delivery_rate' => $sent ? round($delivered / $sent * 100, 1) : 0.0,
                'read_rate' => $delivered ? round($read / $delivered * 100, 1) : 0.0,
            ],
            'daily' => $daily,
            'inboundDaily' => $inboundDaily,
            'byCategory' => $byCategory,
            'topCampaigns' => $topCampaigns,
            'counts' => [
                'customers' => Customer::count(),
                'leads' => Customer::where('type', 'lead')->count(),
                'templates' => MessageTemplate::count(),
                'approved' => MessageTemplate::where('status', 'APPROVED')->count(),
                'pending' => MessageTemplate::where('status', 'PENDING')->count(),
            ],
            'recent' => ActivityLog::with('user')->latest()->limit(8)->get(),
        ]);
    }
}
