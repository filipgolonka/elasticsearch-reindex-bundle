<?php

namespace FilipGolonka\ElasticsearchReindexBundle\Config;

final class ElasticSearchConfig
{
    private $name;

    private $type;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public static function withNameAndType(string $name, string $type): ElasticSearchConfig
    {
        return new self($name, $type);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
