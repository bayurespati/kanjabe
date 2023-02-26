<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\Activity;
use App\Models\Image;
use App\Models\Progress;
use App\Models\Regional;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityService
{
    /**
     * Create
     */
    public function create($request)
    {
        try {
            $payload = $request->only('title', 'description', 'user_id', 'date');
            $model = Activity::make($payload);
            $model->progress = 0;
            return $model->save();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Update
     */
    public function update($request, $model)
    {
        try {
            $model->description = $request->description;
            $model->title       = $request->title;
            $model->update();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Update progress
     */
    public function updateProgress($request)
    {
        try {
            DB::transaction(function () use ($request) {
                $activity = Activity::where('id', $request->activity_id)->first();
                $activity->progress = $request->progress;
                $activity->update();

                $model = new Progress();
                $model->progress    = $request->progress;
                $model->description = $request->description;
                $model->activity_id = $request->activity_id;
                $model->lokasi      = $request->lokasi;
                $model->long_lat    = $request->long_lat['lat'] . ',' . $request->long_lat['lng'];
                $model->jam         = $request->jam;
                if ($request->photo != null)
                    $model->photo       = activityImage($request);

                $model->save();
            });

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Get on progress
     */
    public function getOnProgress($request)
    {
        $users = User::where('is_active', true)->where('role_id', 1)->with(['activity' => function ($activity) use ($request) {
            $activity->where('progress', '<', 100);
            if ($request->date != null)
                $activity->where('date', 'like', '%' . $request->date . '%');
            $activity->withCount('progressDetail');
        }, 'witel', 'mitra']);

        if ($request->user_id != null)
            $users->where('id', $request->user_id);

        if ($request->regional_id != null)
            $users->where('regional_id', $request->regional_id);

        if ($request->mitra_id != null)
            $users->where('mitra_id', $request->mitra_id);

        return $users->where('role_id', 1)->where('is_active', true)->get();
    }


    /**
     * Get Done
     */
    public function getDone($request)
    {
        $users = User::where('is_active', true)->where('role_id', 1)->with(['activity' => function ($activity) use ($request) {
            $activity->where('progress', '=', 100);
            if ($request->date != null)
                $activity->where('created_at', 'like', '%' . $request->date . '%');
            $activity->withCount('progressDetail');
        }]);

        if ($request->user_id != null)
            $users->where('id', $request->user_id);

        if ($request->regional_id != null)
            $users->where('regional_id', $request->regional_id);

        if ($request->mitra_id != null)
            $users->where('mitra_id', $request->mitra_id);

        return $users->where('role_id', 1)->where('is_active', true)->get();
    }


    /**
     * Get Done And Progress
     */
    public function getDoneAndProgress($request)
    {
        return [
            "done" => $this->getUsersActivityAllStatus($request, "done"),
            "on_progress" => $this->getUsersActivityAllStatus($request, "on_progress")
        ];
    }

    private function getUsersActivityAllStatus($request, $status = null)
    {
        $users_done = User::where('is_active', true)->where('role_id', 1)
            ->with(['activity' => function ($activity) use ($request, $status) {
                if ($status == "done")
                    $activity->where('progress', '=', 100);
                if ($status == "on_progress")
                    $activity->where('progress', '<', 100);

                if ($request->date != null)
                    $activity->where('created_at', 'like', '%' . $request->date . '%');
                $activity->withCount('progressDetail');
            }]);

        if ($request->user_id != null)
            $users_done->where('id', $request->user_id);

        if ($request->regional_id != null)
            $users_done->where('regional_id', $request->regional_id);

        if ($request->mitra_id != null)
            $users_done->where('mitra_id', $request->mitra_id);

        return $users_done->get();
    }


    /**
     * Get Progress detail 
     */
    public function getProgress($request)
    {
        return Activity::where('id', $request->activity_id)->with('progressDetail')->get();
    }


    /**
     * Get Overview  
     */
    public function overview($request)
    {
        $users = User::where('is_active', true)->where('role_id', 1);

        if ($request->regional_id != null)
            $users->where('regional_id', $request->regional_id);
        if ($request->mitra_id != null)
            $users->where('mitra_id', $request->mitra_id);
        $users = $users->pluck('id');

        $today = Carbon::today()->format('Y-m-d');
        $date = $request->date == null ? $today : $request->date;

        $completed = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->where('progress', '=', 100)->count();
        $progress = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->whereBetween('progress', [1, 99])->count();
        $todo = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->where('progress', '=', 0)->count();
        if ($request->date == null)
            $pending = Activity::whereIn('user_id', $users)->whereDate('date', '<', $date)->where('progress', '<', 100)->count();
        else
            $pending = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->where('progress', '<', 100)->count();

        return [
            [
                "name" => "pending",
                "value" => $pending
            ],
            [
                "name" => "todo",
                "value" => $todo
            ],
            [
                "name" => "progress",
                "value" => $progress
            ],
            [
                "name" => "completed",
                "value" => $completed
            ],
        ];
    }


    /**
     * Get Per regional
     */
    public function getPerRegional($request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $date = $request->date == null ? $today : $request->date;
        $datas = [];
        $regionals = Regional::all();
        foreach ($regionals as $regional) {
            $users = User::where('regional_id', $regional->id)->where('is_active', true)->where('role_id', 1)->pluck('id');
            $progress = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->whereBetween('progress', [1, 99])->count();
            $todo = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->where('progress', '=', 0)->count();
            $completed = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->where('progress', '=', 100)->count();

            if ($request->date == null)
                $pending = strlen($date) == 7 ? 0 : Activity::whereIn('user_id', $users)->where('progress', '<', 100)->whereDate('date', '<', $date)->count();
            else
                $pending = Activity::whereIn('user_id', $users)->where('progress', '<', 100)->where('date', 'like', '%' . $date . '%')->count();

            $total = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->count();

            array_push($datas, [
                'name' => $regional->alias,
                'pending' => $pending,
                'todo' => $todo,
                'progress' => $progress,
                'completed' => $completed,
                'total' => $total,
                'total_karyawan' => sizeOf($users)
            ]);
        }

        return [
            "regional" => $datas,
            "mitra" => $this->getPermitra($request)
        ];
    }

    /**
     * Get Per mitra 
     */
    public function getPerMitra($request)
    {
        $today = $request->data ?? Carbon::today()->format('Y-m-d');
        $date = $request->date == null ? $today : $request->date;
        $datas = [];
        $mitras = Mitra::all();
        foreach ($mitras as $mitra) {
            $users = User::where('mitra_id', $mitra->id)->where('is_active', true)->where('role_id', 1)->pluck('id');
            $progress = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->whereBetween('progress', [1, 99])->count();
            $completed = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->where('progress', '=', 100)->count();
            $todo = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->where('progress', '=', 0)->count();
            $pending = strlen($date) == 7 ? 0 : Activity::whereIn('user_id', $users)->where('progress', '<', 100)->whereDate('date', '<', $date)->count();
            $total = Activity::whereIn('user_id', $users)->where('date', 'like', '%' . $date . '%')->count();

            array_push($datas, [
                'name' => $mitra->alias,
                'pending' => $pending,
                'todo' => $todo,
                'progress' => $progress,
                'completed' => $completed,
                'total' => $total,
                'total_karyawan' => sizeOf($users)
            ]);
        }
        return $datas;
    }


    /**
     * Delete data
     *
     */
    public function delete($model)
    {
        return $model->delete();
    }


    /**
     * Validate create
     */
    public function addValidate($request, $model = null)
    {
        $validate = [
            'title' => 'required'
        ];

        $messages = [
            'title.required' => 'Judul tidak boleh kosong',
        ];

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }


    /**
     * Validate create
     */
    public function updateValidate($request, $model = null)
    {
        $validate = [
            'description' => 'required',
            'progress' => 'numeric|between:0,100',
        ];

        $messages = [
            'description.required' => 'Deskripsi tidak boleh kosong',
            'progress.between'     => 'Nilai progress melebihi batas',
        ];

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }
}
