<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface AthleteWeightRepository
{
    public function removeAll(): void;

    public function save(AthleteWeight $weight): void;

    public function find(SerializableDateTime $on): AthleteWeight;

    public function findAll(): AthleteWeights;
}
