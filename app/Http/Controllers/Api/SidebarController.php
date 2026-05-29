<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SidebarService;
use Illuminate\Http\JsonResponse;

class SidebarController extends Controller
{
    private SidebarService $sidebarService;

    public function __construct(SidebarService $sidebarService)
    {
        $this->sidebarService = $sidebarService;
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->sidebarService->getItems()
        );
    }
}
