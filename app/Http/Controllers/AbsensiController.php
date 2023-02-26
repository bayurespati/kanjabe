<?php

namespace App\Http\Controllers;

use App\Services\AbsensiService;
use Illuminate\Http\Request;
use App\Exports\PersonalExport;
use App\Exports\PersonalByRegionalExport;
use App\Models\Absensi;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;


class AbsensiController extends Controller
{
    private $absensiService;

    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }


    /**
     * Get All Data Absensi
     *
     */
    public function getAll(Request $request)
    {
        $size = $request->size != null ? $request->size : 10;

        $data = Absensi::with('detailAbsensi')->paginate($size);

        return response()->json($data, 200);
    }


    /**
     * Get daily personal
     *
     */
    public function getUserDaily(Request $request)
    {
        try {
            $data = $this->absensiService->dailyPersonal($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get daily personal
     *
     */
    public function getUserWeekly(Request $request)
    {
        try {
            $data = $this->absensiService->weeklyPersonal($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Report personal
     *
     */
    public function getUserReport(Request $request)
    {
        try {
            $data = $this->absensiService->reportPersonal($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Summary 
     *
     */
    public function summary(Request $request)
    {
        try {
            $data = $this->absensiService->summary($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Data Users Monthly
     *
     */
    public function getUsersMonthly(Request $request)
    {
        try {
            $data = $this->absensiService->usersMonthly($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Data Users Daily
     *
     */
    public function getUsersDaily(Request $request)
    {
        try {
            $data = $this->absensiService->usersDaily($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Employe 
     *
     */
    public function getEmploye(Request $request)
    {
        try {
            $data = $this->absensiService->getEmploye($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Export personal
     *
     */
    public function exportPersonal(Request $request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;

        return Excel::download(new PersonalExport($request, $year, $month), $request->name . '.xlsx');
    }


    /**
     * Get Export by Regional 
     *
     */
    public function exportUserByRegional(Request $request)
    {
        try {
            $month = $request->month == null ? Carbon::now()->month : $request->month;
            $year = $request->year == null ? Carbon::now()->year : $request->year;

            return Excel::download(new PersonalByRegionalExport($request, $year, $month),  $year . '-' . $month . '.xlsx');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * Check In
     *
     */
    public function checkIn(Request $request)
    {
        try {
            //Check if Already check in
            if ($this->absensiService->isCheckIn($request))
                throw new \Exception('{"message":["Anda sudah check in hari ini"]}');

            //Validate
            $validate = $this->absensiService->validateCheckIn($request);
            if (is_object($validate))
                throw new \Exception($validate);

            //Check in
            $data = $this->absensiService->checkIn($request);

            if (!$data)
                throw new \Exception('{"message":["Gagal Check in"]}');

            return response()->json([
                'message' => 'Berhasil check in',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Check Out
     *
     */
    public function checkOut(Request $request, Absensi $absensi)
    {
        try {
            //Check if Already check out
            if ($this->absensiService->isCheckOut($absensi))
                throw new \Exception('{"message":["Anda sudah check out hari ini"]}');

            //Validate
            $validate = $this->absensiService->validateCheckOut($request, $absensi);
            if (is_object($validate))
                throw new \Exception($validate);

            //Check out
            $data = $this->absensiService->checkOut($request, $absensi);

            if (!$data)
                throw new \Exception('{"message":["Gagal Check out"]}');

            return response()->json([
                'message' => 'Berhasil check out',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }
}
