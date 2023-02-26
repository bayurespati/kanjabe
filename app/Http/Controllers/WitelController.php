<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WitelService;
use App\Models\Witel;

class WitelController extends Controller
{
    private $witelService;

    public function __construct(WitelService $witelService)
    {
        $this->witelService = $witelService;
    }


    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {

        $data = Witel::orderBy('name')->get();

        return response()->json($data, 200);
    }


    /**
     * Get By Regional ID 
     *
     */
    public function getByRegionalId(Request $request)
    {

        $data = Witel::where('regional_id',  $request->regional_id)->orderBy('name')->get();

        return response()->json($data, 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {

        try {
            $validate = $this->witelService->validate($request);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->witelService->create($request);

            return response()->json(['message' => 'Berhasil membuat witel'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, Witel $witel)
    {
        try {
            $validate = $this->witelService->validate($request, $witel);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->witelService->update($request, $witel);

            return response()->json(['message' => 'Berhasil memperbaharui data witel'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy(Witel $witel)
    {
        $witelService = $this->witelService->delete($witel);

        if (!$witelService)
            return response()->json(['message' => 'Gagal menghapus data witel'], 400);

        return response()->json(['message' => 'Berhasil menghapus data witel'], 200);
    }
}
