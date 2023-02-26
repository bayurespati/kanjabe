<?php

namespace App\Http\Controllers;

use App\Exports\OvertimePersonalExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\OvertimeService;
use Illuminate\Http\Request;
use App\Models\Overtime;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    private $overtimeService;

    public function __construct(OvertimeService $overtimeService)
    {
        $this->overtimeService = $overtimeService;
    }

    /**
     * Get All Overtime
     *
     */
    public function getAll(Request $request)
    {
        $size = $request->size != null ? $request->size : 10;

        $data = Overtime::paginate($size);

        return response()->json($data, 200);
    }


    /**
     * Get All Overtime
     *
     */
    public function getById(Request $request)
    {
        $data = Overtime::where('id', $request->id)->with(['user', 'detailOvertime', 'logApproval' => function ($log) {
            return $log->with('user');
        }])->first();

        return response()->json($data, 200);
    }


    /**
     * Get Today Personal
     *
     */
    public function getTodayPersonal(Request $request)
    {
        $data = Overtime::where('user_id', $request->user_id)->where('created_at', 'LIKE', '%' . date('Y-m-d') . '%')->with('detailOvertime')->first();

        return response()->json($data, 200);
    }


    /**
     * Get Report personal
     *
     */
    public function getReportPersonal(Request $request)
    {
        try {
            $data = $this->overtimeService->reportPersonal($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get List Approval
     * 
     */
    public function getApprove(Request $request)
    {
        try {
            $data = $this->overtimeService->listApprove($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Check In
     *
     */
    public function checkIn(Request $request)
    {
        try {
            //Validate hari libur
            if (!$this->overtimeService->isAbsen($request))
                throw new \Exception('{"message":["Anda belum melakukan absensi hari ini"]}');

            //Validate hari libur
            if ($this->overtimeService->isHoliday())
                throw new \Exception('{"message":["Hari libur tidak bisa lembur"]}');

            //Validate WFH
            if ($this->overtimeService->isWfh($request))
                throw new \Exception('{"message":["WFH tidak boleh lembur"]}');

            //Validate
            $validate = $this->overtimeService->validateCheckIn($request);
            if (is_object($validate))
                throw new \Exception($validate);

            //Check if Already check in
            if ($this->overtimeService->isCheckIn($request))
                throw new \Exception('{"message":["Anda sudah check in lembur"]}');

            //Check in
            if (!$this->overtimeService->checkIn($request))
                throw new \Exception('{"message":["Gagal check in lembur"]}');

            return response()->json(['message' => 'Berhasil check in lembur'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Check Out
     *
     */
    public function checkOut(Request $request, Overtime $overtime)
    {
        try {

            //Validate 
            $validate = $this->overtimeService->validateCheckOut($request);
            if (is_object($validate))
                throw new \Exception($validate);

            //Check if Already check out
            if ($this->overtimeService->isCheckOut($overtime))
                throw new \Exception('{"message":["Anda sudah check out lembur"]}');

            //Check out
            if (!$this->overtimeService->checkOut($request, $overtime))
                throw new \Exception('{"message":["Gagal check out lembur"]}');

            return response()->json(['message' => 'Berhasil check out lembur'], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Aprrove
     *
     */
    public function approved(Request $request, Overtime $overtime)
    {
        try {

            if ($overtime->status == 'reject' || $overtime->status == 'done')
                throw new \Exception('{"message":["Action Tidak bisa di lakukan"]}');

            if ($overtime->current != $request->username)
                throw new \Exception('{"message":["Anda tidak memiliki otoritas untuk aksi ini"]}');

            //Approve
            if (!$this->overtimeService->approved($request, $overtime))
                throw new \Exception('{"message":["Gagal Approve lemburan"]}');

            return response()->json(['message' => 'Berhasil proses'], 200);
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
        $name = $request->name == null ? "File" : $request->name;
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;
        return Excel::download(new OvertimePersonalExport($request, $year, $month), $name . '.xlsx');
    }
}
