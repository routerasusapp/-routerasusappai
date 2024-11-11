<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Title implements JsonSerializable
{
    #[ORM\Column(type: 'string', name: "title", length: 255)]
    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = mb_substr($value, 0, 255);
    }

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
