<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\UpdateDocumentCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\DocumentEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Entities\TranscriptionEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;

class UpdateDocumentCommandHandler
{
    public function __construct(
        private LibraryItemRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws LibraryItemNotFoundException
     */
    public function handle(UpdateDocumentCommand $cmd): AbstractLibraryItemEntity
    {
        $item =
            $cmd->id instanceof AbstractLibraryItemEntity
            ? $cmd->id : $this->repo->ofId($cmd->id);

        if (
            ($item instanceof DocumentEntity)
            || ($item instanceof CodeDocumentEntity)
            || ($item instanceof TranscriptionEntity)
            || ($item instanceof SpeechEntity)
            || ($item instanceof ConversationEntity)
        ) {
            if ($cmd->title) {
                $item->setTitle($cmd->title);
            }
        }

        if (
            $item instanceof DocumentEntity
            || $item instanceof CodeDocumentEntity
        ) {
            if ($cmd->content) {
                $item->setContent($cmd->content);
            }
        }

        if ($cmd->visibility) {
            $item->setVisibility($cmd->visibility);
        }

        return $item;
    }
}
