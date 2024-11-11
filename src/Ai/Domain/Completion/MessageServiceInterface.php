<?php

declare(strict_types=1);

namespace Ai\Domain\Completion;

use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Token;
use Billing\Domain\ValueObjects\Count;
use Generator;

interface MessageServiceInterface extends AiServiceInterface
{
    /**
     * @return Generator<int,Token,null,Count>
     * @throws ApiException
     * @throws DomainException
     */
    public function generateMessage(
        Model $model,
        MessageEntity $message,
    ): Generator;
}
