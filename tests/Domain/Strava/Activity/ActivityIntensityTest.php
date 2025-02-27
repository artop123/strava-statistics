<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\KeyValueBasedAthleteRepository;
use App\Domain\Strava\Ftp\DbalFtpRepository;
use App\Domain\Strava\Ftp\EFtpRepository;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Ftp\EFtpRepositoryBuilder;
use App\Tests\Domain\Strava\Ftp\FtpBuilder;

class ActivityIntensityTest extends ContainerTestCase
{
    private ActivityIntensity $activityIntensity;
    private FtpRepository $ftpRepository;
    private EFtpRepository $eftpRepository;
    private AthleteRepository $athleteRepository;

    public function testCalculateWithFtp(): void
    {
        $ftp = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftp);

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withAveragePower(250)
            ->withMovingTimeInSeconds(3600)
            ->build();

        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity),
        );
    }

    public function testCalculateWithHeartRate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withAverageHeartRate(171)
            ->withMovingTimeInSeconds(3600)
            ->build();

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity),
        );
    }

    public function testCalculateWithEFtpDisabled(): void
    {
        $eftpRepository = EFtpRepositoryBuilder::fromDefaults()
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(0)
            ->build();

        $activity = ActivityBuilder::fromDefaults()
            ->withStartDateTime(SerializableDateTime::fromString('2023-03-31'))
            ->withAveragePower(200)
            ->withMovingTimeInSeconds(3600)
            ->withSportType(SportType::RIDE)
            ->build();

        $eftpIntensity = new ActivityIntensity(
            $this->athleteRepository,
            $this->ftpRepository,
            $eftpRepository
        );

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $this->assertNull(
            $eftpIntensity->calculate($activity),
        );
    }

    public function testCalculateWithFTPInsteadOfEFtp(): void
    {
        $ftp = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-01-01'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftp);

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $eftpRepository = EFtpRepositoryBuilder::fromDefaults()
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 100
            )
            ->withNumberOfMonths(3)
            ->build();

        $activity = ActivityBuilder::fromDefaults()
            ->withStartDateTime(SerializableDateTime::fromString('2023-03-31'))
            ->withAveragePower(250)
            ->withMovingTimeInSeconds(3600)
            ->withSportType(SportType::RIDE)
            ->build();

        $eftpIntensity = new ActivityIntensity(
            $this->athleteRepository,
            $this->ftpRepository,
            $eftpRepository
        );

        $this->assertEquals(
            100,
            $eftpIntensity->calculate($activity),
        );
    }

    public function testCalculateWithEFtp(): void
    {
        $eftpRepository = EFtpRepositoryBuilder::fromDefaults()
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(3)
            ->build();

        $activity = ActivityBuilder::fromDefaults()
            ->withStartDateTime(SerializableDateTime::fromString('2023-03-31'))
            ->withAveragePower(200)
            ->withMovingTimeInSeconds(3600)
            ->withSportType(SportType::RIDE)
            ->build();

        $eftpIntensity = new ActivityIntensity(
            $this->athleteRepository,
            $this->ftpRepository,
            $eftpRepository
        );

        $this->assertEquals(
            100,
            $eftpIntensity->calculate($activity),
        );
    }

    public function testCalculateShouldBeNull(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withMovingTimeInSeconds(3600)
            ->build();

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $this->assertNull(
            $this->activityIntensity->calculate($activity),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpRepository = new DbalFtpRepository(
            $this->getConnection()
        );
        $this->athleteRepository = new KeyValueBasedAthleteRepository(
            $this->getContainer()->get(KeyValueStore::class)
        );
        $this->eftpRepository = EFtpRepositoryBuilder::fromDefaults()
            ->withNumberOfMonths(0)
            ->build();

        $this->activityIntensity = new ActivityIntensity(
            $this->athleteRepository,
            $this->ftpRepository,
            $this->eftpRepository
        );
    }
}
