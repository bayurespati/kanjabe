<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    private $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }


    /**
     * Get Data All Present User
     *
     */
    public function getPresentAllUser(Request $request)
    {
        try {
            $data = $this->dashboardService->dashbordPresentAllUser($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Data All Status User
     *
     */
    public function getStatusAllUser(Request $request)
    {
        try {
            $data = $this->dashboardService->dashbordStatusAllUser($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }
}
