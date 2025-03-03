<?php

namespace App\Tests\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Strava\Athlete\Weight\ImportAthleteWeight\AthleteWeightsFromEnvFile;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use PHPUnit\Framework\TestCase;

class AthleteWeightsFromEnvFileTest extends TestCase
{
    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid ATHLETE_WEIGHTS detected in .env file. Make sure the string is valid JSON'));
        AthleteWeightsFromEnvFile::fromString('{"lol}', UnitSystem::METRIC);
    }

    public function testReadFromJsonShouldThrow(): void
    {
        $path = realpath(__DIR__ . '/Data/invalid.json');
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid ATHLETE_WEIGHTS detected in .env file. Make sure the string is valid JSON'));
        AthleteWeightsFromEnvFile::fromString($path, UnitSystem::METRIC);
    }

    public function testReadFromJson(): void
    {
        $path = realpath(__DIR__ . '/Data/weight.json');
        $result = AthleteWeightsFromEnvFile::fromString($path, UnitSystem::METRIC);
        $this->assertCount(3, $result->getAll());
    }
}
