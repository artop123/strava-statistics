<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

final class DashboardMinYear
{
    private function __construct(private ?int $year)
    {
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public static function from(?int $year): self
    {
        return new self($year);
    }
}
