<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class HomeController
{
    public function index(Request $request): Response
    {
        $registerUrl = config('app.register_route_enabled')
            ? route('register')
            : route('login');

        $contactHref = config('app.contact_route_enabled')
            ? route('contact')
            : 'mailto:' . config('app.contact_email');

        $html = view('public/home', [
            'title' => 'Hotezo — Multi-Hotel Booking & OTA Commission Platform',
            'description' => 'List your hotel, take direct & OTA bookings, and let Hotezo auto-calculate every commission, GST, TDS and TCS — down to the exact rupee the hotel earns.',
            'isLoggedIn' => Auth::check(),
            'registerUrl' => $registerUrl,
            'contactHref' => $contactHref,
            'demoModeEnabled' => (bool) config('app.demo_mode', false),
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'Hotezo',
                'description' => 'Multi-hotel booking hub and back-office platform with automatic OTA commission, GST, TDS and TCS calculation.',
                'url' => config('app.url'),
            ],
        ], 'public');

        return Response::html($html);
    }

    public function privacy(Request $request): Response
    {
        return $this->legalPlaceholder('Privacy Policy', 'privacy');
    }

    public function terms(Request $request): Response
    {
        return $this->legalPlaceholder('Terms of Service', 'terms');
    }

    private function legalPlaceholder(string $title, string $slug): Response
    {
        $html = view('public/legal', [
            'title' => $title . ' — Hotezo',
            'description' => $title . ' for the Hotezo platform.',
            'pageTitle' => $title,
            'slug' => $slug,
            'contactHref' => 'mailto:' . config('app.contact_email'),
        ], 'public');

        return Response::html($html);
    }
}
