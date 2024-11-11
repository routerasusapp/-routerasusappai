<?php

declare(strict_types=1);

namespace Presentation\EventStream;

use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\ValueObjects\Token;
use Generator;
use JsonSerializable;
use Presentation\Resources\Api\MessageResource;
use Throwable;

class Streamer
{
    private bool $isOpened = false;

    /** @return void  */
    public function open(): void
    {
        if (connection_aborted()) {
            exit;
        }

        if ($this->isOpened) {
            return;
        }

        $this->isOpened = true;
        ob_end_flush();
    }

    /**
     * @param string $event 
     * @param null|string|array|JsonSerializable $data 
     * @param null|string $id 
     * @return void 
     */
    public function sendEvent(
        string $event,
        null|string|array|JsonSerializable $data = null,
        ?string $id = null,
    ): void {
        echo "event: " . $event . PHP_EOL;

        if (!is_null($data)) {
            echo "data: " . (is_string($data) ? $data : json_encode($data)) . PHP_EOL;
        }

        echo "id: " . ($id ?: microtime(true)) . PHP_EOL . PHP_EOL;
        flush();
    }

    /** @return void  */
    public function close(): void
    {
        if (!$this->isOpened) {
            return;
        }

        $this->isOpened = false;
    }

    public function stream(
        Generator $generator
    ): void {
        $this->open();

        try {
            foreach ($generator as $item) {
                if ($item instanceof Token) {
                    $this->sendEvent('token', $item);
                    continue;
                }

                if ($item instanceof MessageEntity) {
                    $message = new MessageResource($item);
                    $this->sendEvent('message', $message);
                }
            }
        } catch (Throwable $th) {
            $this->sendEvent('error', $th->getMessage());
        }


        $this->close();
    }
}
