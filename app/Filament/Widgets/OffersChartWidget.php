<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OffersChartWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return __('translate.dashboard.offers_chart');
    }

    protected static string $chartId  = 'offers-chart';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // Surinkti paskutines 7 dienas (imtinai, šiandiena įskaityta)
        $days = collect();
        $now = now();
        for ($i = 6; $i >= 0; $i--) {
            $dateKey = $now->copy()->subDays($i)->format('Y-m-d');
            $days->put($dateKey, 0);
        }

        // Užklausiam offerių kiekį pagal dienas
        $rows = Offer::query()
            ->select([
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereBetween('created_at', [
                $now->copy()->subDays(6)->startOfDay(),
                $now->endOfDay(),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day', 'asc')
            ->get()
            ->pluck('count', 'day');

        // Užpildom trūkstamas dienas (jei nėra offerių – bus 0)
        foreach ($days as $day => $count) {
            $days[$day] = $rows[$day] ?? 0;
        }

        // Gražūs labeliai (pvz. "05-16 (Kt)")
        Carbon::setLocale('lt');
        $labels = collect($days->keys())->map(fn($d) =>
            Carbon::parse($d)->translatedFormat('m-d (D)')
        )->toArray();

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'            => __('translate.charts.offers.label'), // arba 'Offers'
                'data'             => $days->values()->toArray(),
                'tension'          => 0.4,
                'borderColor'      => '#6366F1',
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
