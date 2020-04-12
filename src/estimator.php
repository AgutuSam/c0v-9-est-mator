<?php

function covid19ImpactEstimator($data)
{
    $stats = jaysonDecode($data);
    $stats = json_decode($stats, true);
    // echo $stats['region']['avgAge'];
    $impactCurrentlyInfected = $stats['reportedCases'] * 10;
    $severeImpactCurrentlyInfected = $stats['reportedCases'] * 50;

    $periodType = $stats['periodType'];

    if ($periodType == "days") {
        $factor = intval($stats['timeToElapse'] / 3);
    } elseif ($periodType == "weeks") {
        $factor = intval(($stats['timeToElapse'] * 7) / 3);
    } elseif ($periodType == "months") {
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

    if ($periodType == "days") {
        $days = intval($stats['timeToElapse']);
    } elseif ($periodType == "weeks") {
        $days = intval($stats['timeToElapse'] * 7);
    } elseif ($periodType == "months") {
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

function jaysonDecode($s)
{
    $s = str_replace(
        array('"',  "'"),
        array('"', '"'),
        $s
    );
    $s = preg_replace('/(\w+):/i', '"\1":', $s);

    return $s;
}
