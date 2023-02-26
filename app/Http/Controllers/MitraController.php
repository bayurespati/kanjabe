<?php

namespace App\Http\Controllers;

use App\Services\MitraService;
use Illuminate\Http\Request;
use App\Models\Mitra;

class MitraController extends Controller
{
    private $mitraService;

    public function __construct(MitraService $mitraService)
    {
        $this->mitraService = $mitraService;
    }


    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {
        $data = Mitra::all();

        return response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {

        try {
            $validate = $this->mitraService->validate($request);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->mitraService->create($request);

            return response()->json(['message' => 'Berhasil membuat mitra'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, Mitra $mitra)
    {
        try {
            $validate = $this->mitraService->validate($request, $mitra);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->mitraService->update($request, $mitra);

            return response()->json(['message' => 'Berhasil memperbaharui data mitra'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy(Mitra $mitra)
    {
        $mitraService = $this->mitraService->delete($mitra);

        if (!$mitraService)
            return response()->json(['message' => 'Gagal menghapus data mitra'], 400);

        return response()->json(['message' => 'Berhasil menghapus data mitra'], 200);
    }
}
