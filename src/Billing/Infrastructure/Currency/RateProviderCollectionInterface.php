<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency;

use IteratorAggregate;

/**
 * @extends IteratorAggregate<string,RateProviderInterface>
 */
interface RateProviderCollectionInterface extends IteratorAggregate
{
}
