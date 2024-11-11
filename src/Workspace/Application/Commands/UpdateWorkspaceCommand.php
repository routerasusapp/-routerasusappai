<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\UpdateWorkspaceCommandHandler;
use Workspace\Domain\ValueObjects\Address;
use Workspace\Domain\ValueObjects\Name;

#[Handler(UpdateWorkspaceCommandHandler::class)]
class UpdateWorkspaceCommand
{
    public Id $id;
    public ?Name $name = null;
    public ?Id $ownerId = null;
    public ?Address $address = null;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }

    public function setName(string $name): void
    {
        $this->name = new Name($name);
    }

    public function setOwnerId(string $ownerId): void
    {
        $this->ownerId = new Id($ownerId);
    }

    public function setAddress(array $address): void
    {
        $this->address = new Address($address);
    }
}
