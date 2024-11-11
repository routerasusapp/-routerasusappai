<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use File\Domain\Entities\FileEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class FileResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private FileEntity $file)
    {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $file = $this->file;

        return [
            'id' => $file->getId(),
            'created_at' => new DateTimeResource($file->getCreatedAt()),
            'updated_at' => new DateTimeResource($file->getUpdatedAt()),

            'storage' => $file->getStorage(),
            'object_key' => $file->getObjectKey(),
            'url' => $file->getUrl(),
            'size' => $file->getSize(),
        ];
    }
}
