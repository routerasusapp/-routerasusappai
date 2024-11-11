<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\MessageEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class MessageResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private MessageEntity $message)
    {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $res = $this->message;

        return [
            'object' => 'message',
            'id' => $res->getId(),
            'model' => $res->getModel(),
            'role' => $res->getRole(),
            'content' => $res->getContent(),
            'quote' => $res->getQuote(),
            'cost' => $res->getCost(),
            'created_at' => new DateTimeResource($res->getCreatedAt()),
            'assistant' => $res->getAssistant() ?  new AssistantResource($res->getAssistant()) : null,
            'parent_id' => $res->getParent() ? $res->getParent()->getId() : null,
            'user' => $res->getUser() ? new UserResource($res->getUser()) : null,
            'image' => $res->getImage() ? new ImageFileResource($res->getImage()) : null,
        ];
    }
}
