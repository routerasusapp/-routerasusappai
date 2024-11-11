<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum ItemType: string implements JsonSerializable
{
    case IMAGE = 'image';
    case DOCUMENT = 'document';
    case CODE_DOCUMENT = 'code_document';
    case TRANSCRIPTION = 'transcription';
    case SPEECH = 'speech';
    case CONVERSATION = 'conversation';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
