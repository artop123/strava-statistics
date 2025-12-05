<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance;

use App\Domain\Strava\Activity\Activities;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class YearlyStatistics
{
    use ProvideTimeFormats;

    private function __construct(
        private Activities $activities,
        private Years $years,
    ) {
    }

    public static function create(
        Activities $activities,
        Years $years,
    ): self {
        return new self($activities, $years);
    }

    private function getDaysInYear(int $year): int
    {
        $now = new \DateTimeImmutable();
        $startOfYear = SerializableDateTime::fromString(sprintf('%d-01-01', $year));
        $endOfYear = $year === (int) $now->format('Y')
            ? SerializableDateTime::fromDateTimeImmutable($now->modify('-1 day'))
            : SerializableDateTime::fromString(sprintf('%d-12-31', $year));

        return $startOfYear->diff($endOfYear)->days + 1;
    }

    /**
     * @return array<int, mixed>
     */
    public function getStatistics(): array
    {
        $statistics = [];
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->years as $year) {
            $statistics[(string) $year] = [
                'year' => $year,
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'totalCalories' => 0,
                'movingTimeInSeconds' => 0,
            ];
        }

        $statistics = array_reverse($statistics, true);

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activities as $activity) {
            $year = $activity->getStartDate()->getYear();

            if (!array_key_exists($year, $statistics)) {
                continue;
            }

            ++$statistics[$year]['numberOfRides'];
            $statistics[$year]['totalDistance'] += $activity->getDistance()->toFloat();
            $statistics[$year]['totalElevation'] += $activity->getElevation()->toFloat();
            $statistics[$year]['movingTimeInSeconds'] += $activity->getMovingTimeInSeconds();
            $statistics[$year]['totalCalories'] += $activity->getCalories();
        }

        $statistics = array_values($statistics);
        foreach ($statistics as $key => &$statistic) {
            $daysInYear = $this->getDaysInYear($statistic['year']->toInt());

            $movingTimePerDay = $daysInYear > 0
                ? floor($statistic['movingTimeInSeconds'] / $daysInYear)
                : 0;
            $movingTimePerActivity = $statistic['numberOfRides'] > 0
                ? floor($statistic['movingTimeInSeconds'] / $statistic['numberOfRides'])
                : 0;

            $statistic['movingTime'] = floor($statistic['movingTimeInSeconds'] / 3600);
            $statistic['movingTimePerDay'] = $this->formatDurationForHumans((int) $movingTimePerDay);
            $statistic['movingTimePerActivity'] = $this->formatDurationForHumans((int) $movingTimePerActivity);
            $statistic['differenceInDistanceYearBefore'] = null;
            $statistic['differenceInMovingTimeYearBefore'] = null;

            if (isset($statistics[$key + 1]['totalDistance'])) {
                $statistic['differenceInDistanceYearBefore'] = Kilometer::from($statistic['totalDistance'] - $statistics[$key + 1]['totalDistance']);
            }

            if (isset($statistics[$key + 1]['movingTimeInSeconds'])) {
                $movingTimeYearBefore = floor($statistics[$key + 1]['movingTimeInSeconds'] / 3600);
                $difference = $statistic['movingTime'] - $movingTimeYearBefore;
                $statistic['differenceInMovingTimeYearBefore'] = $difference;
            }

            $statistics[$key]['totalDistanceKm'] = Kilometer::from($statistic['totalDistance']);
            $statistics[$key]['totalElevation'] = Meter::from($statistic['totalElevation']);
        }

        return $statistics;
    }
}
