<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\Count;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
#[ORM\Table(name: 'library_item')]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: "discr", type: Types::STRING)]
#[ORM\DiscriminatorMap([
    'document' => DocumentEntity::class,
    'code_document' => CodeDocumentEntity::class,
    'image' => ImageEntity::class,
    'transcription' => TranscriptionEntity::class,
    'speech' => SpeechEntity::class,
    'conversation' => ConversationEntity::class,
])]
abstract class AbstractLibraryItemEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Model::class, columnPrefix: false)]
    private Model $model;

    #[ORM\Column(type: Types::SMALLINT, enumType: Visibility::class, name: 'visibility')]
    private Visibility $visibility;

    #[ORM\Embedded(class: Count::class, columnPrefix: 'used_credit_')]
    protected Count $cost;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkspaceEntity $workspace;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserEntity $user;

    #[ORM\Column(type: Types::JSON, name: 'request_params')]
    private $requestParams; //! Fix this

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?RequestParams $requestParams = null,
        ?Count $cost = null,
        ?Visibility $visibility = null,
    ) {
        $this->id = new Id();
        $this->visibility = $visibility ?? Visibility::PRIVATE;
        $this->cost = $cost ?? new Count(0);
        $this->model = $model;
        $this->requestParams = $requestParams ?: new RequestParams;
        $this->createdAt = new DateTimeImmutable();
        $this->workspace = $workspace;
        $this->user = $user;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function setVisibility(Visibility $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function getCost(): Count
    {
        return $this->cost;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getRequestParams(): RequestParams
    {
        return is_array($this->requestParams)
            ? RequestParams::fromArray($this->requestParams) : $this->requestParams;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getWorkspace(): WorkspaceEntity
    {
        return $this->workspace;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
