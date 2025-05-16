<?php

declare(strict_types=1);

namespace LeonWitlif\SpaceTraders\ModelParser;

class ClassType
{
    private(set) readonly string $name;
    /** @var array<int, ClassProperty> */
    private(set) array $properties;

    public function __construct(Model $model)
    {
        $this->name = $model->name;
        $this->properties = [];

        foreach ((array) $model->getProperties() as $name => $property) {
            $this->properties[] = new ClassProperty($model, $name, $property);
        }
    }

    public function render(callable $namespaceCallback): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespaceCallback($this->name)};

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
        return \implode(PHP_EOL.'        ', \array_map(fn (object $property) => $property->render(), $this->properties));
    }
}
