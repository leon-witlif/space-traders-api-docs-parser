<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use LeonWitlif\SpaceTraders\ModelParser\ClassType;
use LeonWitlif\SpaceTraders\ModelParser\EnumType;
use LeonWitlif\SpaceTraders\ModelParser\Model;

$outputDir = \realpath($argv[2]);

$models = Model::fromDirectory($argv[1]);

$classes = [];
$enums = [];

foreach ($models as $model) {
    if (Model::isClassType($model)) {
        $classes[] = new ClassType($model);
        continue;
    }

    if (Model::isEnumType($model)) {
        $enums[] = new EnumType($model);
        continue;
    }
}

$fileLocations = [];

$namespaceCallback = function (string $name) use ($outputDir, &$fileLocations) {
    if (\str_starts_with($name, 'Agent')) {
        $fileLocations[$name] = $outputDir.'/Agent/'.$name.'.php';

        return 'LeonWitlif\\SpaceTraders\\SDK\\Model\\Agent';
    }

    if (\str_starts_with($name, 'Contract')) {
        $fileLocations[$name] = $outputDir.'/Contract/'.$name.'.php';

        return 'LeonWitlif\\SpaceTraders\\SDK\\Model\\Contract';
    }

    if (\str_starts_with($name, 'Faction') || \in_array($name, ['SystemFaction', 'WaypointFaction'])) {
        $fileLocations[$name] = $outputDir.'/Faction/'.$name.'.php';

        return 'LeonWitlif\\SpaceTraders\\SDK\\Model\\Faction';
    }

    if (\str_starts_with($name, 'Ship') || \in_array($name, ['Cooldown', 'Extraction', 'ExtractionYield', 'RepairTransaction', 'ScannedShip', 'ScrapTransaction', 'Siphon', 'SiphonYield', 'Survey', 'SurveyDeposit'])) {
        $fileLocations[$name] = $outputDir.'/Ship/'.$name.'.php';

        return 'LeonWitlif\\SpaceTraders\\SDK\\Model\\Ship';
    }

    if (\str_starts_with($name, 'System') || \str_starts_with($name, 'Waypoint') || \in_array($name, ['ActivityLevel', 'Chart', 'ConnectedSystem', 'Construction', 'ConstructionMaterial', 'JumpGate', 'Market', 'MarketTradeGood', 'MarketTransaction', 'ScannedSystem', 'ScannedWaypoint', 'SupplyLevel', 'TradeGood', 'TradeSymbol'])) {
        $fileLocations[$name] = $outputDir.'/System/'.$name.'.php';

        return 'LeonWitlif\\SpaceTraders\\SDK\\Model\\System';
    }

    $fileLocations[$name] = $outputDir.'/'.$name.'.php';

    return 'LeonWitlif\\SpaceTraders\\SDK\\Model\\Custom';
};

foreach ($classes as $class) {
    \file_put_contents($outputDir.'/'.$class->name.'.php', $class->render($namespaceCallback));
}

foreach ($enums as $enum) {
    \file_put_contents($outputDir.'/'.$enum->name.'.php', $enum->render($namespaceCallback));
}

foreach ($fileLocations as $name => $targetLocation) {
    \rename($outputDir.'/'.$name.'.php', $targetLocation);
}
