<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ThemeSubscriber implements EventSubscriberInterface
{
    /** @var list<string> */
    public const THEMES = ['editorial', 'premium', 'chaleureux', 'futuriste'];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $theme = $request->cookies->get('theme');

        if (is_string($theme) && in_array($theme, self::THEMES, true)) {
            $request->attributes->set('app_theme', $theme);
        }
    }
}
