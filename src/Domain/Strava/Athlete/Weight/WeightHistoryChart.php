<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class WeightHistoryChart
{
    private function __construct(
        private AthleteWeights $weights,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        AthleteWeights $weights,
        SerializableDateTime $now,
    ): self {
        return new self(
            weights: $weights,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $dates = $this->weights->map(function (AthleteWeight $weight) {
            return $weight->getOn()->format('Y-m-d');
        });

        $dates = array_unique(array_merge($dates, [$this->now->format('Y-m-d')]));
        sort($dates);
        $weights = [];

        foreach ($dates as $date) {
            $datetime = SerializableDateTime::fromString($date);
            $startDate = (clone $datetime)->modify('-6 days')->setTime(0, 0, 0);
            $endDate = (clone $datetime)->setTime(23, 59, 59);

            $filteredWeights = $this->weights->filter(function (AthleteWeight $weight) use ($startDate, $endDate) {
                return $weight->getOn() >= $startDate && $weight->getOn() <= $endDate;
            });

            $sum = 0;
            $count = 0;

            foreach ($filteredWeights as $weight) {
                $sum += $weight->getWeightInKg()->toFloat();
                ++$count;
            }

            $avg = $count ? $sum / $count : null;

            if ($avg) {
                $weights[] = [$date, round($avg, 2)];
            }
        }

        if (empty($weights)) {
            return [];
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'top' => '2%',
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'time',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => [
                            'year' => '{yyyy}',
                            'month' => '{MMM}',
                            'day' => '',
                            'hour' => '{HH}:{mm}',
                            'minute' => '{HH}:{mm}',
                            'second' => '{HH}:{mm}:{ss}',
                            'millisecond' => '{hh}:{mm}:{ss} {SSS}',
                            'none' => '{yyyy}-{MM}-{dd}',
                        ],
                    ],
                    'splitLine' => [
                        'show' => true,
                        'lineStyle' => [
                            'color' => '#E0E6F1',
                        ],
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} kg',
                    ],
                    'min' => min(array_column($weights, 1)) - 10,
                ],
            ],
            'series' => [
                [
                    'name' => 'Weight',
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'yAxisIndex' => 0,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...$weights,
                    ],
                ],
            ],
        ];
    }
}
