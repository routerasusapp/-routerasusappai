<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Billing\Domain\ValueObjects\Count;
use Assistant\Domain\Entities\AssistantEntity;
use Ai\Domain\ValueObjects\Instructions;
use Ai\Domain\ValueObjects\MessageRole;
use Ai\Domain\ValueObjects\Quote;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\ImageFileEntity;
use Shared\Domain\ValueObjects\Id;
use User\Domain\Entities\UserEntity;

#[ORM\Entity]
#[ORM\Table(name: 'message')]
class MessageEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Model::class, columnPrefix: false)]
    private Model $model;

    #[ORM\Column(type: Types::STRING, enumType: MessageRole::class, name: 'role', length: 24)]
    private MessageRole $role;

    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content; //! Files???

    #[ORM\Embedded(class: Quote::class, columnPrefix: false)]
    private Quote $quote;

    #[ORM\Embedded(class: Count::class, columnPrefix: 'used_credit_')]
    private Count $cost;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: ConversationEntity::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ConversationEntity $conversation;

    #[ORM\ManyToOne(targetEntity: MessageEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?MessageEntity $parent = null;

    #[ORM\ManyToOne(targetEntity: AssistantEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AssistantEntity $assistant = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?UserEntity $user = null;

    #[ORM\ManyToOne(targetEntity: ImageFileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'file_id')]
    private ?ImageFileEntity $image = null;

    public static function userMessage(
        ConversationEntity $conversation,
        Content $content,
        UserEntity $user,
        Model $model,
        ?MessageEntity $parent = null,
        ?AssistantEntity $assistant = null,
        ?Quote $quote = null,
        ?ImageFileEntity $image = null,
    ): self {
        $entity = new self();
        $entity->id = new Id();
        $entity->model = $model;
        $entity->role = MessageRole::USER;
        $entity->content = $content;
        $entity->quote = $quote ?? new Quote();
        $entity->cost = new Count(0);
        $entity->createdAt = new DateTimeImmutable();
        $entity->conversation = $conversation;
        $entity->user = $user;
        $entity->parent = $parent;
        $entity->assistant = $assistant;
        $entity->image = $image;

        $conversation->addMessage($entity);

        return $entity;
    }

    public static function assistantMessage(
        ConversationEntity $conversation,
        Content $content,
        MessageEntity $parent,
        Count $cost,
        Model $model,
        ?AssistantEntity $assistant = null,
    ): self {
        $entity = new self();

        $entity->id = new Id();
        $entity->role = MessageRole::ASSISTANT;
        $entity->content = $content;
        $entity->quote = new Quote();
        $entity->cost = $cost;
        $entity->createdAt = new DateTimeImmutable();
        $entity->conversation = $conversation;
        $entity->parent = $parent;
        $entity->assistant = $assistant;
        $entity->model = $model;

        $conversation->addMessage($entity);

        return $entity;
    }

    private function __construct()
    {
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getRole(): MessageRole
    {
        return $this->role;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function getCost(): Count
    {
        return $this->cost;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getConversation(): ConversationEntity
    {
        return $this->conversation;
    }

    public function getParent(): ?MessageEntity
    {
        return $this->parent;
    }

    public function getAssistant(): ?AssistantEntity
    {
        return $this->assistant;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function getImage(): ?ImageFileEntity
    {
        return $this->image;
    }
}
