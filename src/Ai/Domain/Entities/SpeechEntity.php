<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\Count;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\FileEntity;
use User\Domain\Entities\UserEntity;
use Voice\Domain\Entities\VoiceEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class SpeechEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content;

    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'output_file_id')]
    private FileEntity $outputFile;

    #[ORM\ManyToOne(targetEntity: VoiceEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', name: 'voice_id')]
    private ?VoiceEntity $voice = null;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        FileEntity $outputFile,
        Title $title,
        Content $content,
        VoiceEntity $voice,

        ?RequestParams $request = null,
        ?Count $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $voice->getModel(),
            $request,
            $cost,
            $visibility
        );

        $this->title = $title;
        $this->content = $content;
        $this->outputFile = $outputFile;
        $this->voice = $voice;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(Title $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getOutputFile(): FileEntity
    {
        return $this->outputFile;
    }

    public function getVoice(): ?VoiceEntity
    {
        return $this->voice;
    }

    public function getContent(): Content
    {
        return $this->content;
    }
}
