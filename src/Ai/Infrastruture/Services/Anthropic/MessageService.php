<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\Anthropic;

use Ai\Domain\Completion\MessageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Token;
use Ai\Infrastruture\Services\CostCalculator;
use Billing\Domain\ValueObjects\Count;
use File\Infrastructure\FileService;
use Generator;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use Override;
use Throwable;

class MessageService implements MessageServiceInterface
{
    private array $models = [
        'claude-3-opus-20240229',
        'claude-3-sonnet-20240229',
        'claude-3-haiku-20240307'
    ];

    public function __construct(
        private Client $client,
        private Gpt3Tokenizer $tokenizer,
        private CostCalculator $calc,
        private FileService $fs,
    ) {
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function generateMessage(
        Model $model,
        MessageEntity $message
    ): Generator {
        $inputTokensCount = 0;
        $messages = [];
        $current = $message;
        $maxMessages = 20;

        $imageCount = 0;
        while (true) {
            if ($current->getContent()->value) {
                $content = [];
                $tokens = 0;
                $img = $current->getImage();

                if (
                    $current->getRole()->value == 'user'
                    && $img
                    && $imageCount < 2
                ) {
                    try {
                        $imgContent = $this->fs->getFileContents($img);

                        $ext = $img->getExtension();
                        if ($ext == 'jpeg') {
                            $ext = 'jpg';
                        }

                        $content[] = [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'image/' .  $ext,
                                'data' => base64_encode($imgContent)
                            ]
                        ];

                        $imageCount++;
                    } catch (Throwable $th) {
                        // Unable to load image
                    }
                }

                $text = $current->getContent()->value;

                if ($current->getQuote()->value) {
                    $text
                        .= "\n\nThe user is referring to this in particular:\n"
                        . $current->getQuote()->value;
                }

                $content[] = [
                    'type' => 'text',
                    'text' => $text
                ];

                $tokens = $this->tokenizer->count($text);

                if ($tokens + $inputTokensCount > 200000) {
                    break;
                }

                $inputTokensCount += $tokens;

                array_unshift($messages, [
                    'role' => $current->getRole()->value,
                    'content' => $content
                ]);
            }

            if (count($messages) >= $maxMessages) {
                break;
            }

            if ($current->getParent()) {
                $current = $current->getParent();
                continue;
            }

            break;
        }

        if ($messages && $messages[0]['role'] !== 'user') {
            array_unshift($messages, [
                'role' => 'user',
                'content' => '-'
            ]);
        }

        $body = [
            'messages' => $messages,
            'model' => $model->value,
            'max_tokens' => 4096,
            'stream' => true,
        ];

        $assistant = $message->getAssistant();
        if ($assistant) {
            $body['system'] = $assistant->getInstructions()->value;
        }

        $resp = $this->client->sendRequest('POST', '/v1/messages', $body);
        $stream = new StreamResponse($resp);

        $inputTokensCount = 0;
        $outputTokensCount = 0;
        foreach ($stream as $data) {
            $type = $data->type ?? null;

            if ($type === 'error') {
                throw new ApiException($data->error->message);
            }

            if ($type == 'message_start') {
                $inputTokensCount += $data->message->usage->input_tokens ?? 0;
                $outputTokensCount += $data->message->usage->output_tokens ?? 0;

                continue;
            }

            if ($type == 'content_block_delta') {
                $content = $data->delta->text ?? null;

                if ($content) {
                    yield new Token($content);
                }

                continue;
            }

            if ($type == 'message_delta') {
                $inputTokensCount += $data->usage->input_tokens ?? 0;
                $outputTokensCount += $data->usage->output_tokens ?? 0;

                continue;
            }
        }

        $inputCost = $this->calc->calculate(
            $inputTokensCount,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $outputTokensCount,
            $model,
            CostCalculator::OUTPUT
        );

        return new Count($inputCost->value + $outputCost->value);
    }
}
