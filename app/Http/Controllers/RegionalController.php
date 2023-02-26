<?php

namespace App\Http\Controllers;

use App\Services\RegionalService;
use Illuminate\Http\Request;
use App\Models\Regional;

class RegionalController extends Controller
{
    private $regionalService;

    public function __construct(RegionalService $regionalService)
    {
        $this->regionalService = $regionalService;
    }


    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {
        $data = Regional::all();

        return response()->json($data, 200);
    }


    /**
     * Display a listing of the resource.
     *
     */
    public function getWithWitel(Request $request)
    {
        $data = Regional::with('witel')->get();

        return response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {

        try {
            $validate = $this->regionalService->validate($request);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->regionalService->create($request);

            return response()->json(['message' => 'Berhasil membuat regional'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, Regional $regional)
    {
        try {
            $validate = $this->regionalService->validate($request, $regional);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->regionalService->update($request, $regional);

            return response()->json(['message' => 'Berhasil memperbaharui data regional'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy(Regional $regional)
    {
        $regionalService = $this->regionalService->delete($regional);

        if (!$regionalService)
            return response()->json(['message' => 'Gagal menghapus data regional'], 400);

        return response()->json(['message' => 'Berhasil menghapus data regional'], 200);
    }
}
