<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use LeonWitlif\SpaceTraders\ModelParser\ClassType;
use LeonWitlif\SpaceTraders\ModelParser\Model;

$outputDir = \realpath($argv[2]);

$models = Model::fromDirectory($argv[1]);

$classes = [];

foreach ($models as $model) {
    if (Model::isClassType($model)) {
        $classes[] = new ClassType($model);
    }
}

foreach ($classes as $class) {
    file_put_contents($outputDir.'/'.$class->name.'.php', $class->render());
}
