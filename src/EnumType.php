<?php

declare(strict_types=1);

namespace LeonWitlif\SpaceTraders\ModelParser;

readonly class EnumType
{
    private(set) string $name;
    private(set) array $cases;

    public function __construct(Model $model)
    {
        $this->name = $model->name;
        $this->cases = $model->getEnumCases();
    }

    public function render(callable $namespaceCallback): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespaceCallback($this->name)};

enum $this->name : string
{
    {$this->renderCases()}
}

PHP;
    }

    private function renderCases(): string
    {
        return \implode(PHP_EOL.'    ', \array_map(fn (string $case) => "case $case = '$case';", $this->cases));
    }
}
