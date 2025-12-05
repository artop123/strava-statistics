<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\DashboardMinYear;
use PHPUnit\Framework\TestCase;

class DashboardMinYearTest extends TestCase
{
    public function testFrom(): void
    {
        $this->assertEquals(
            null,
            DashboardMinYear::from(null)->getYear()
        );

        $this->assertEquals(
            2023,
            DashboardMinYear::from(2023)->getYear()
        );
    }
}
