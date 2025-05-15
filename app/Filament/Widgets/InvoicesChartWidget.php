<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoicesChartWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return __('translate.dashboard.invoices_chart');
    }

    protected static string $chartId = 'invoices-chart';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // Kiek dienų atgal rodyti (pvz. 14 dienų)
        $days = 14;
        $dates = collect();
        $now = now();

        // Surenkam dienas atgal, užpildom 0
        for ($i = $days - 1; $i >= 0; $i--) {
            $dateKey = $now->copy()->subDays($i)->format('Y-m-d');
            $dates->put($dateKey, 0);
        }

        // Užklausiam kiek sąskaitų per dieną
        $rows = Invoice::query()
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->where('created_at', '>=', $now->copy()->subDays($days - 1)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count', 'date');

        // Priskiriam duomenis
        foreach ($dates as $date => $count) {
            $dates[$date] = $rows[$date] ?? 0;
        }

        // Lietuviški labeliai: pvz., "05-19"
        Carbon::setLocale('lt');
        $labels = $dates->keys()->map(fn($d) =>
            Carbon::createFromFormat('Y-m-d', $d)->translatedFormat('m-d')
        )->toArray();

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'            => __('translate.charts.invoices.label'), // pvz. "Sąskaitos"
                'data'             => $dates->values()->toArray(),
                'tension'          => 0.4,
                'borderColor'      => '#10B981',
                'backgroundColor'  => 'transparent',
                'fill'             => false,
                'pointRadius'      => 2,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min'         => 0,
                    'beginAtZero' => true,
                    'ticks'       => [
                        'stepSize'  => 1,
                        'precision' => 0,
                    ],
                ],
                'x' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
