<?php

namespace App\Tests\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Strava\Athlete\Weight\ImportAthleteWeight\AthleteWeightHistoryFromEnvFile;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use PHPUnit\Framework\TestCase;

class AthleteWeightHistoryFromEnvFileTest extends TestCase
{
    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid ATHLETE_WEIGHT_HISTORY detected in .env file. Make sure the string is valid JSON'));
        AthleteWeightHistoryFromEnvFile::fromString('{"lol}', UnitSystem::METRIC);
    }

    public function testItShouldThrowOnInvalidDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "YYYY-MM-DD" set in ATHLETE_WEIGHT_HISTORY in .env file'));
        AthleteWeightHistoryFromEnvFile::fromString('{"YYYY-MM-DD": 220}', UnitSystem::METRIC);
    }

    public function testReadFromJsonShouldThrow(): void
    {
        $path = realpath(__DIR__.'/Data/invalid.json');
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid ATHLETE_WEIGHTS detected in .env file. Make sure the string is valid JSON'));
        AthleteWeightsFromEnvFile::fromString($path, UnitSystem::METRIC);
    }

    public function testReadFromJson(): void
    {
        $path = realpath(__DIR__.'/Data/weight.json');
        $this->assertFileExists($path, "File missing: $path");

        $result = AthleteWeightsFromEnvFile::fromString($path, UnitSystem::METRIC);
        $this->assertCount(3, $result->getAll());
    }
}
