<?php

declare(strict_types=1);

if (!\file_exists($argv[1])) {
    exit('Invalid file given');
}

readonly class Endpoint
{
    public function __construct(
        public string $method,
        public string $path,
        public string $operation,
    ) {
    }

    public function __toString(): string
    {
        return \sprintf('%-30s %5s %s', $this->operation, \strtoupper($this->method), $this->path);
    }
}

function parseOperationName(string $operationId): string
{
    $dashPosition = \stripos($operationId, '-');

    if ($dashPosition !== false) {
        $operation = \substr($operationId, 0, $dashPosition).\ucfirst(\substr($operationId, $dashPosition + 1));

        return parseOperationName($operation);
    }

    return $operationId;
}

$endpoints = [];

$content = \file_get_contents($argv[1]);
$json = \json_decode($content, true);

foreach (\array_slice($json['paths'], 0) as $path => $rawEndpoints) {
    foreach ($rawEndpoints as $method => $endpoint) {
        $tag = \current($endpoint['tags']);

        if (!\array_key_exists($tag, $endpoints)) {
            $endpoints[$tag] = [new Endpoint($method, $path, parseOperationName($endpoint['operationId']))];
        } else {
            $endpoints[$tag][] = new Endpoint($method, $path, parseOperationName($endpoint['operationId']));
        }
    }
}

foreach ($endpoints as &$group) {
    \usort($group, fn (Endpoint $a, Endpoint $b) => \strcmp($a->path, $b->path));
}

unset($group);

foreach ($endpoints as $groupName => $group) {
    echo '---'.$groupName.'---'.PHP_EOL;

    foreach ($group as $endpoint) {
        echo $endpoint.PHP_EOL;
    }

    echo PHP_EOL;
}
