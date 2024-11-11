<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\Count;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\ImageFileEntity;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class ImageEntity extends AbstractLibraryItemEntity
{
    #[ORM\ManyToOne(targetEntity: ImageFileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'output_file_id')]
    private ImageFileEntity $outputFile;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        ImageFileEntity $outputFile,

        Model $model,
        ?RequestParams $request = null,
        ?Count $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $model,
            $request,
            $cost,
            $visibility
        );

        $this->outputFile = $outputFile;
    }

    public function getOutputFile(): ImageFileEntity
    {
        return $this->outputFile;
    }
}
