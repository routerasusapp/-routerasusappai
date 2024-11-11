<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Exceptions\MessageNotFoundException;
use Billing\Domain\ValueObjects\Count;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Shared\Domain\ValueObjects\Id;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class ConversationEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\OneToMany(targetEntity: MessageEntity::class, mappedBy: 'conversation', cascade: ['persist', 'remove'])]
    private Collection $messages;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user
    ) {
        parent::__construct(
            $workspace,
            $user,
            new Model(),
        );

        $this->title = new Title('New conversation');
        $this->messages = new ArrayCollection();
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

    public function addMessage(MessageEntity $message): self
    {
        $this->messages->add($message);

        $this->cost = new Count(
            (float) $this->cost->value + (float) $message->getCost()->value
        );

        return $this;
    }

    /**
     * @return Traversable<MessageEntity>
     * @throws Exception
     */
    public function getMessages(): Traversable
    {
        yield from $this->messages->getIterator();
    }

    public function findMessage(Id|MessageEntity $id): MessageEntity
    {
        if ($id instanceof MessageEntity) {
            $id = $id->getId();
        }

        /** @var MessageEntity */
        foreach ($this->messages as $message) {
            if ($message->getId()->equals($id)) {
                return $message;
            }
        }

        throw new MessageNotFoundException($id);
    }

    public function getLastMessage(): ?MessageEntity
    {
        return  $this->messages->last() ?: null;
    }
}
