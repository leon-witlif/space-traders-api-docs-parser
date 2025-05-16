<?php

declare(strict_types=1);

namespace LeonWitlif\SpaceTraders\ModelParser;

class Model
{
    /** @var array<int, Model> */
    private static array $models = [];

    /**
     * @return array<int, Model>
     */
    public static function fromDirectory(string $directoryPath): array
    {
        $directoryPath = \realpath($directoryPath);

        $directoryHandle = \opendir($directoryPath);
        \assert($directoryHandle !== false);

        $jsonFileContents = [];

        while (($file = \readdir($directoryHandle)) !== false) {
            if (\is_file($directoryPath.'/'.$file)) {
                $jsonFileContents[$file] = \file_get_contents($directoryPath.'/'.$file);
            }
        }

        \closedir($directoryHandle);

        return \array_map([Model::class, 'fromJson'], \array_keys($jsonFileContents), $jsonFileContents);
    }

    public static function fromJson(string $name, string $json): Model
    {
        self::$models[] = $model = new self(\mb_substr($name, 0, -5), \json_decode($json));

        return $model;
    }

    public static function isClassType(Model|string $modelOrModelName): bool
    {
        return self::getModelForTypeCheck($modelOrModelName)?->model->type === 'object';
    }

    public static function isEnumType(Model|string $modelOrModelName): bool
    {
        return self::isStringType($modelOrModelName) && isset(self::getModelForTypeCheck($modelOrModelName)?->model->enum);
    }

    public static function isStringType(Model|string $modelOrModelName): bool
    {
        return self::getModelForTypeCheck($modelOrModelName)?->model->type === 'string';
    }

    public static function isIntType(Model|string $modelOrModelName): bool
    {
        return self::getModelForTypeCheck($modelOrModelName)?->model->type === 'number' && self::getModelForTypeCheck($modelOrModelName)?->model->format === 'integer';
    }

    public static function isFloatType(Model|string $modelOrModelName): bool
    {
        return self::getModelForTypeCheck($modelOrModelName)?->model->type === 'number' && self::getModelForTypeCheck($modelOrModelName)?->model->format === 'double';
    }

    private static function getModelForTypeCheck(Model|string $modelOrModelName): ?Model
    {
        if (\get_debug_type($modelOrModelName) === self::class) {
            return $modelOrModelName;
        }

        return \array_find(self::$models, fn (Model $model) => $model->name === $modelOrModelName);
    }

    private function __construct(
        private(set) readonly string $name,
        private readonly \stdClass $model
    ) {
    }

    public function getProperties(): \stdClass
    {
        return self::isClassType($this) ? $this->model->properties : new \stdClass();
    }

    public function getEnumCases(): array
    {
        return $this->model->enum;
    }

    public function getRequiredProperties(): array
    {
        return self::isClassType($this) ? $this->model->required ?? [] : [];
    }
}
