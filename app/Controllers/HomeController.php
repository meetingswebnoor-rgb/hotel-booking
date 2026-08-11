<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class HomeController
{
    public function index(Request $request): Response
    {
        $html = view('public/home', [
            'title' => 'Hotezo — Book Direct. Manage Everything.',
            'description' => 'Hotezo lists your hotel to the world and runs the back office: OTA commissions, Hotezo commission, GST/TDS/TCS, settlements, and invoices — automatically.',
        ], 'public');

        return Response::html($html);
    }
}
