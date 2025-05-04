<?php

declare(strict_types=1);

$inputDir = realpath($argv[1]);
$outputDir = realpath($argv[2]);

$fileContents = [];

if ($handle = opendir($inputDir)) {
    while (($file = readdir($handle)) !== false) {
        if (is_file($inputDir.'/'.$file)) {
            $fileContents[$file] = file_get_contents($inputDir.'/'.$file);
        }
    }

    closedir($handle);
}

$jsonModels = array_map('json_decode', $fileContents);

static $sEnumTypes = [];
static $sStringTypes = [];
static $sIntegerTypes = [];
static $sFloatTypes = [];

class ST_Class
{
    private(set) string $name;
    /** @var array<int, ST_Property> */
    private(set) array $properties;

    public function __construct(string $fileName, stdClass $model)
    {
        $this->name = explode('.', $fileName)[0];
        $this->properties = [];

        if ($model->type !== 'object') {
            return;
        }

        $this->properties = array_map(fn (string $name, stdClass $property) => new ST_Property($model, $name, $property), array_keys((array) $model->properties), (array) $model->properties);
    }

    public function render(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

readonly class $this->name
{
    public function __construct(
        {$this->renderProperties()}
    ) {
    }
}

PHP;
    }

    private function renderProperties(): string
    {
        return implode(PHP_EOL.'        ', array_map(fn (object $property) => $property->render(), $this->properties));
    }
}

class ST_Property
{
    private(set) string $name;
    private(set) string $type {
        get => match ($this->type) {
            'integer' => 'int',
            'boolean' => 'bool',
            default => $this->type,
        };
    }
    private(set) string $itemsType;
    private(set) bool $required;

    public function __construct(stdClass $model, string $name, stdClass $property)
    {
        global $sEnumTypes, $sStringTypes, $sIntegerTypes, $sFloatTypes;

        $this->name = $name;
        $this->type = $property->type ?? mb_substr($property->{'$ref'}, 2, -5);

        if (in_array($this->type, $sEnumTypes) || in_array($this->type, $sStringTypes)) {
            $this->type = 'string';
        }

        if (in_array($this->type, $sIntegerTypes)) {
            $this->type = 'integer';
        }

        if (in_array($this->type, $sFloatTypes)) {
            $this->type = 'float';
        }

        $this->itemsType = $this->type === 'array' ? ($property->items->type ?? mb_substr($property->items->{'$ref'}, 2, -5)) : '';
        $this->required = in_array($name, $model->required ?? []);
    }

    public function render(): string
    {
        return $this->renderDoc().'private(set) '.($this->required ? '' : '?').$this->type.' $'.$this->name.',';
    }

    private function renderDoc(): string
    {
        if ($this->type !== 'array') {
            return '';
        }

        return '/** @var array<int, '.$this->itemsType.'> */'.PHP_EOL.'        ';
    }
}

foreach ($jsonModels as $file => $jsonModel) {
    $name = mb_substr($file, 0, -5);

    if (isset($jsonModel->enum)) {
        $sEnumTypes[] = $name;

        continue;
    }

    if ($jsonModel->type === 'string') {
        $sStringTypes[] = $name;

        continue;
    }

    if ($jsonModel->type === 'number') {
        switch ($jsonModel->format) {
            case 'integer':
                $sIntegerTypes[] = $name;
                break;
            case 'double':
                $sFloatTypes[] = $name;
                break;
        }
    }
}

$models = array_map(fn (string $file, stdClass $model) => new ST_Class($file, $model), array_keys($jsonModels), $jsonModels);

foreach ($models as $model) {
    if (!count($model->properties)) {
        continue;
    }

    file_put_contents($outputDir.'/'.$model->name.'.php', $model->render());
}
