<?php

use RobinIngelbrecht\PHPUnitCoverageTools\MinCoverage\MinCoverageRule;

return [
    new MinCoverageRule(
        pattern: MinCoverageRule::TOTAL,
        minCoverage: 95,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Console\*',
        minCoverage: 100,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Infrastructure\*',
        minCoverage: 90,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Domain\*',
        minCoverage: 96,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Domain\Manifest\*',
        minCoverage: 100,
        exitOnLowCoverage: true
    ),
];
