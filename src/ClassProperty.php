<?php

declare(strict_types=1);

namespace LeonWitlif\SpaceTraders\ModelParser;

class ClassProperty
{
    private(set) readonly string $name;
    private(set) string $type {
        get => match ($this->type) {
            'integer' => 'int',
            'boolean' => 'bool',
            default => $this->type,
        };
    }
    private(set) readonly string $itemsType;
    private(set) readonly bool $required;

    public function __construct(Model $model, string $name, \stdClass $property)
    {
        $this->name = $name;
        $this->type = $this->determineType($property);
        $this->itemsType = $this->type === 'array' ? $this->determineType($property->items) : '';
        $this->required = \in_array($this->name, $model->getRequiredProperties());
    }

    private function determineType(\stdClass $property): string
    {
        $type = $property->type ?? \mb_substr($property->{'$ref'}, 2, -5);

        if (Model::isEnumType($type) || Model::isStringType($type)) {
            $type = 'string';
        }

        if (Model::isIntType($type)) {
            $type = 'integer';
        }

        if (Model::isFloatType($type)) {
            $type = 'float';
        }

        return $type;
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
