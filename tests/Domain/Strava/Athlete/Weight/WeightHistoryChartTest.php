<?php

namespace App\Tests\Domain\Strava\Athlete\Weight;

use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Domain\Strava\Athlete\Weight\DbalAthleteWeightRepository;
use App\Domain\Strava\Athlete\Weight\WeightHistoryChart;
use App\Infrastructure\ValueObject\Measurement\Mass\Gram;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class WeightHistoryChartTest extends ContainerTestCase
{
    private AthleteWeightRepository $athleteWeightRepository;

    public function testChartIsNotEmpty(): void
    {
        $weightOne = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-04-01'))
            ->withWeightInGrams(Gram::from(74000))
            ->build();
        $this->athleteWeightRepository->save($weightOne);
        $WeightTwo = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-05-25'))
            ->withWeightInGrams(Gram::from(75000))
            ->build();
        $this->athleteWeightRepository->save($WeightTwo);
        $weightThree = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-08-01'))
            ->withWeightInGrams(Gram::from(70000))
            ->build();
        $this->athleteWeightRepository->save($weightThree);
        $weightFour = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-09-24'))
            ->withWeightInGrams(Gram::from(60000))
            ->build();
        $this->athleteWeightRepository->save($weightFour);
        $now = SerializableDateTime::fromString('2023-09-25');

        $chart = WeightHistoryChart::create(
            $this->athleteWeightRepository->findAll(), $now
        )->build();

        $this->assertNotEmpty($chart);
    }

    public function testChartIsEmpty(): void
    {
        $this->athleteWeightRepository->removeAll();
        $now = SerializableDateTime::fromString('2023-09-25');

        $chart = WeightHistoryChart::create(
            $this->athleteWeightRepository->findAll(), $now
        )->build();

        $this->assertEmpty($chart);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->athleteWeightRepository = new DbalAthleteWeightRepository(
            $this->getConnection()
        );
    }
}
