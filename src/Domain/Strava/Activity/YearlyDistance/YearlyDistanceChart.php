<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class YearlyDistanceChart
{
    private function __construct(
        private Activities $activities,
        private TranslatorInterface $translator,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        Activities $activities,
        TranslatorInterface $translator,
        SerializableDateTime $now,
    ): self {
        return new self(
            activities: $activities,
            translator: $translator,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $months = [
            '01' => $this->translator->trans('Jan'),
            '02' => $this->translator->trans('Feb'),
            '03' => $this->translator->trans('Mar'),
            '04' => $this->translator->trans('Apr'),
            '05' => $this->translator->trans('May'),
            '06' => $this->translator->trans('Jun'),
            '07' => $this->translator->trans('Jul'),
            '08' => $this->translator->trans('Aug'),
            '09' => $this->translator->trans('Sep'),
            '10' => $this->translator->trans('Oct'),
            '11' => $this->translator->trans('Nov'),
            '12' => $this->translator->trans('Dec'),
        ];

        $xAxisLabels = [];
        foreach ($months as $month) {
            $xAxisLabels = [...$xAxisLabels, ...array_fill(0, 31, $month)];
        }

        $series = [];
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->activities->getUniqueYears() as $year) {
            $series[(string) $year] = [
                'name' => (string) $year,
                'type' => 'line',
                'smooth' => true,
                'showSymbol' => false,
                'data' => [],
            ];

            $runningSum = 0;

            $startDate = SerializableDateTime::fromString(sprintf('%s-01-01', $year));
            $endDate = SerializableDateTime::fromString(sprintf('%s-12-31', $year));

            while ($startDate->isBeforeOrOn($endDate)) {
                $activitiesOnThisDay = $this->activities->filterOnDate($startDate);

                if ($startDate->isAfter($this->now)) {
                    break;
                }

                $runningSum += $activitiesOnThisDay->sum(
                    fn (Activity $activity) => $activity->getMovingTimeInSeconds()
                );

                $series[(string) $year]['data'][] = floor($runningSum / 3600);
                $startDate = $startDate->addDays(1);
            }
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'grid' => [
                'left' => '40px',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'interval' => 31,
                    ],
                    'data' => $xAxisLabels,
                ],
            ],
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'Duration (hours)',
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 50,
                ],
            ],
            'series' => array_values($series),
        ];
    }
}
