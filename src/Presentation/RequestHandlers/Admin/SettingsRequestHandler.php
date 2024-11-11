<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Billing\Infrastructure\Currency\RateProviderCollectionInterface;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\FileSystem\CdnAdapterCollectionInterface;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;

#[Route(path: '/settings', method: RequestMethod::GET)]
#[Route(
    path: '/settings/[general|brand|billing|payments|credits|rate-providers|openai|anthropic|elevenlabs|stabilityai|gcp|azure|clipdrop|mail|smtp|policies|accounts|public-details|recaptcha|appearance|storage|cdn:name]?',
    method: RequestMethod::GET
)]
#[Route(
    path: '/settings/[identity-providers:group]/[google|linkedin|facebook|github:name]?',
    method: RequestMethod::GET
)]
#[Route(
    path: '/settings/[script-tags:group]/[google-analytics|google-tag-manager|intercom|custom:name]?',
    method: RequestMethod::GET
)]
#[Route(
    path: '/settings/[features:group]/[writer|chat|voiceover|imagine|:name]?',
    method: RequestMethod::GET
)]
class SettingsRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private PaymentGatewayFactoryInterface $factory,
        private CdnAdapterCollectionInterface $cdnAdapters,
        private RateProviderCollectionInterface $rateProviders
    ) {
    }

    /**
     * @throws MissingResourceException 
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name');
        $group = $request->getAttribute('group');
        if (!$name) {
            $name = 'index';
        }

        if ($group) {
            $name = $group . '/' . $name;
        }

        $data = [];
        $data['currencies'] = Currencies::getNames();
        $data['payment_gateways'] = $this->factory;
        $data['cdn_adapters'] = $this->cdnAdapters;
        $data['rate_providers'] = $this->rateProviders;

        return new ViewResponse(
            '/templates/admin/settings/' . $name . '.twig',
            $data
        );
    }
}
