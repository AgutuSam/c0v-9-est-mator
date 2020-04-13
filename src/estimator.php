<?php

function covid19ImpactEstimator($data)
{
    $stats = strval($data);
    $stats = preg_replace('/(,|\{)[ \t\n]*(\w+)[ ]*:[ ]*/', '$1"$2":', $stats);
    $stats = json_decode($data, true);

    $impactCurrentlyInfected = $stats['reportedCases'] * 10;
    $severeImpactCurrentlyInfected = $stats['reportedCases'] * 50;

    if ($stats['periodType'] == 'days') {
        $factor = intval($stats['timeToElapse'] / 3);
    } elseif ($stats['periodType'] == 'weeks') {
        $factor = intval(($stats['timeToElapse'] * 7) / 3);
    } elseif ($stats['periodType'] == 'months') {
        $factor = intval(($stats['timeToElapse'] * 30) / 3);
    }

    $impactInfectionsByRequestedTime = $impactCurrentlyInfected * (pow(2, $factor));
    $severeImpactInfectionsByRequestedTime = $severeImpactCurrentlyInfected * (pow(2, $factor));

    $impactSevereCasesByRequestedTime = intval((15 * $impactInfectionsByRequestedTime) / 100);
    $severeImpactSevereCasesByRequestedTime = intval((15 * $severeImpactInfectionsByRequestedTime) / 100);

    $expectedHospitalBedsByRequestedTime = intval((35 * $stats['totalHospitalBeds']) / 100);

    $impactHospitalBedsByRequestedTime = intval(
        $expectedHospitalBedsByRequestedTime - $impactSevereCasesByRequestedTime
    );
    $severeImpactHospitalBedsByRequestedTime = intval(
        $expectedHospitalBedsByRequestedTime - $severeImpactSevereCasesByRequestedTime
    );

    $impactCasesForICUByRequestedTime = intval((5 * $impactInfectionsByRequestedTime) / 100);
    $severeImpactCasesForICUByRequestedTime = intval((5 * $severeImpactInfectionsByRequestedTime) / 100);

    $impactCasesForVentilatorsByRequestedTime = intval((2 * $impactInfectionsByRequestedTime) / 100);
    $severeImpactCasesForVentilatorsByRequestedTime = intval((2 * $severeImpactInfectionsByRequestedTime) / 100);

    if ($stats['periodType'] == 'days') {
        $days = intval($stats['timeToElapse']);
    } elseif ($stats['periodType'] == 'weeks') {
        $days = intval($stats['timeToElapse'] * 7);
    } elseif ($stats['periodType'] == 'months') {
        $days = intval($stats['timeToElapse'] * 30);
    }

    $impactDollarsInFlight = intval(
        (
            (
                $impactInfectionsByRequestedTime * $stats['region']['avgDailyIncomePopulation']
                ) * $stats['region']['avgDailyIncomeInUSD']
                ) * $days
    );
    $severeImpactDollarsInFlight = intval(
        (
            (
                $severeImpactInfectionsByRequestedTime * $stats['region']['avgDailyIncomePopulation']
                ) * $stats['region']['avgDailyIncomeInUSD']
                ) * $days
    );

    $stats = json_encode($stats);
    $estimate = '{
        data: '.$data.',
        impact: {
            currentlyInfected: '.$impactCurrentlyInfected.',
            infectionsByRequestedTime: '.$impactInfectionsByRequestedTime.',
            severeCasesByRequestedTime: '.$impactSevereCasesByRequestedTime.',
            hospitalBedsByRequestedTime: '.$impactHospitalBedsByRequestedTime.',
            casesForICUByRequestedTime: '.$impactCasesForICUByRequestedTime.',
            casesForVentilatorsByRequestedTime: '.$impactCasesForVentilatorsByRequestedTime.',
            dollarsInFlight: '.$impactDollarsInFlight.'
        },
        severeImpact: {
            currentlyInfected: '.$severeImpactCurrentlyInfected.',
            infectionsByRequestedTime: '.$severeImpactInfectionsByRequestedTime.',
            severeCasesByRequestedTime: '.$severeImpactSevereCasesByRequestedTime.',
            hospitalBedsByRequestedTime: '.$severeImpactHospitalBedsByRequestedTime.',
            casesForICUByRequestedTime: '.$severeImpactCasesForICUByRequestedTime.',
            casesForVentilatorsByRequestedTime: '.$severeImpactCasesForVentilatorsByRequestedTime.',
            dollarsInFlight: '.$severeImpactDollarsInFlight.'
        }
    }';

    return $estimate;
}
