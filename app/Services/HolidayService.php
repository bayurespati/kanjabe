<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\Holiday;


class HolidayService
{

    /**
     * Create
     */
    public function create($request)
    {
        try {

            $payload = $request->only('nama', 'tanggal');
            $model = Holiday::make($payload);
            return $model->save();

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Update
     */
    public function update($request, $holiday)
    {
        try {
            $holiday->nama    = $request->nama;
            $holiday->tanggal = $request->tanggal;
            $holiday->update();

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
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
     * Validate input for check in
     */
    public function validate($request, $model = null)
    {

        $id = $model == null ? '' : $model->id;

        $validate = [
            'nama'      => 'required|unique:holidays,nama,' . $id,
            'tanggal'   => 'required|unique:holidays,tanggal,' . $id
        ];

        $messages = [
            'nama.required'     => 'Nama tidak boleh kosong',
            'nama.unique'       => 'Nama sudah ada',
            'tanggal.required'  => 'Tanggal tidak boleh kosong',
            'tanggal.unique'    => 'Tanggal sudah ada',
        ];

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }
}
