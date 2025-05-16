<?php

declare(strict_types=1);

namespace LeonWitlif\SpaceTraders\ModelParser;

class ClassProperty
{
    private(set) readonly string $name;
    private(set) string $type;
    private(set) readonly bool $required;

    private(set) readonly bool $isArray;
    private(set) readonly ?string $itemsType;

    public function __construct(Model $model, string $name, \stdClass $property)
    {
        $this->name = $name;
        $this->type = $this->determineType($property);
        $this->required = \in_array($this->name, $model->getRequiredProperties());

        $this->isArray = $this->type === 'array';
        $this->itemsType = $this->type === 'array' ? $this->determineType($property->items) : null;
    }

    private function determineType(\stdClass $property): string
    {
        if (isset($property->{'$ref'})) {
            return \mb_substr($property->{'$ref'}, 2, -5);
        }

        if (isset($this->isArray) && $this->isArray) {
            if (isset($property->items->{'$ref'})) {
                return \mb_substr($property->items->{'$ref'}, 2, -5);
            }
        }

        return $property->type;
    }

    public function render(): string
    {
        return $this->renderDoc().'public '.($this->required ? '' : '?').$this->getActualType($this->type).' $'.$this->name.',';
    }

    private function renderDoc(): string
    {
        if ($this->isArray) {
            return '/** @var array<int, '.$this->itemsType.'> */'.PHP_EOL.'        ';
        }

        return '';
    }

    private function getActualType(string $type): string
    {
        return match ($type) {
            'integer' => 'int',
            'boolean' => 'bool',
            'WaypointSymbol', 'SystemSymbol' => 'string',
            'ShipComponentCondition', 'ShipComponentIntegrity', 'ShipComponentQuality' => 'float',
            default => $type
        };
    }
}
