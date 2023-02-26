<?php

namespace App\Http\Controllers;

use App\Services\HolidayService;
use Illuminate\Http\Request;
use App\Models\Holiday;

class HolidayController extends Controller
{
    private $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }


    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {
        $size = $request->size != null ? $request->size : 10;

        $holidays = Holiday::orderBy('tanggal', 'asc');

        if ($request->date !== null)
            $holidays = Holiday::where('tanggal', 'like', '%' . $request->date . '%')->orderBy('tanggal', 'asc');

        return response()->json($holidays->paginate($size), 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {

        try {
            $validate = $this->holidayService->validate($request);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->holidayService->create($request);

            return response()->json(['message' => 'Berhasil membuat hari libur'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, Holiday $holiday)
    {

        try {
            $validate = $this->holidayService->validate($request, $holiday);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->holidayService->update($request, $holiday);

            return response()->json(['message' => 'Berhasil memperbaharui data hari libur'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy(Holiday $holiday)
    {
        $holidayService = $this->holidayService->delete($holiday);

        if (!$holidayService)
            return response()->json(['message' => 'Gagal menghapus data holiday'], 400);

        return response()->json(['message' => 'Berhasil menghapus data holiday'], 200);
    }
}
