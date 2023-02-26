<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ActivityService;
use App\Models\Activity;
use App\Models\User;
use App\Exports\AcitivityExport;
use App\Exports\ActivityDetailExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ActivityController extends Controller
{
    private $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }


    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {
        $size = $request->size != null ? $request->size : 10;

        $data = Activity::with(['progressDetail'])->paginate($size);

        return response()->json($data, 200);
    }


    /**
     * Display all.
     * Param regional_id, date
     */
    public function getAll(Request $request)
    {
        $users = User::where('is_active', true);

        if ($request->regional_id != null)
            $users->where('regional_id', $request->regional_id);

        if ($request->mitra_id != null)
            $users->where('mitra_id', $request->mitra_id);

        $users->where('role_id', 1)
            ->with(['activity' => function ($activity) use ($request) {
                return $activity->where('created_at', 'like', '%' . $request->date . '%');
            }, 'regional', 'witel', 'mitra']);

        return response()->json($users->get(), 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {
        try {
            $validate = $this->activityService->addValidate($request);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->activityService->create($request);

            return response()->json(['message' => 'Berhasil membuat activity'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, Activity $activity)
    {
        try {
            $validate = $this->activityService->updateValidate($request, $activity);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->activityService->update($request, $activity);

            return response()->json(['message' => 'Berhasil memperbaharui data activity'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Export 
     *
     */
    public function overview(Request $request)
    {
        try {
            $data = $this->activityService->overview($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return $e->getMessage();
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Export 
     *
     */
    public function export(Request $request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;
        return Excel::download(new AcitivityExport($request, $year, $month),  $year . '-' . $month . '.xlsx');
    }

    /**
     * Get Export 
     *
     */
    public function exportDetail(Request $request)
    {
        return Excel::download(new ActivityDetailExport($request),  $request->date . '.xlsx');
    }


    /**
     * Upload image for activity 
     *
     */
    public function updateProgress(Request $request)
    {
        try {
            $validate = $this->activityService->updateValidate($request);
            if (is_object($validate))
                throw new \Exception($validate);

            $this->activityService->updateProgress($request);

            return response()->json(['message' => 'Berhasil update progress data activity'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy(Activity $activity)
    {
        $activityService = $this->activityService->delete($activity);

        if (!$activityService)
            return response()->json(['message' => 'Gagal menghapus data activity'], 400);

        return response()->json(['message' => 'Berhasil menghapus data activity'], 200);
    }


    /**
     * Get Data on progress
     * With param request user_id, regional_id, date
     *
     */
    public function getOnProgress(Request $request)
    {
        try {
            $data = $this->activityService->getOnProgress($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Data on progress
     * With param request user_id, regional_id, date
     *
     */
    public function getDone(Request $request)
    {
        try {
            $data = $this->activityService->getDone($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Data done and on progress
     * With param request user_id, regional_id, date
     *
     */
    public function getDoneAndProgress(Request $request)
    {
        try {
            $data = $this->activityService->getDoneAndProgress($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Data on progress
     * With param request user_id, regional_id, date
     *
     */
    public function getProgress(Request $request)
    {
        try {
            $data = $this->activityService->getProgress($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Get Data Per regional 
     *
     */
    public function getPerRegional(Request $request)
    {
        try {
            $data = $this->activityService->getPerRegional($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }

    /**
     * Get Data Per mitra 
     *
     */
    public function getPerMitra(Request $request)
    {
        try {
            $data = $this->activityService->getPerMitra($request);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }
}
