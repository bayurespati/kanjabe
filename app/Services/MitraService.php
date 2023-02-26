<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\Mitra;

class MitraService
{

    /**
     * Create
     */
    public function create($request)
    {
        try {

            $payload = $request->only('name', 'alias');
            $model = Mitra::make($payload);
            return $model->save();

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Update
     */
    public function update($request, $mitra)
    {
        try {
            $mitra->name    = $request->name;
            $mitra->alias   = $request->alias;
            $mitra->update();

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
     * Validate 
     */
    public function validate($request, $model = null)
    {

        $id = $model == null ? '' : $model->id;

        $validate = [
            'name'  => 'required|unique:mitra,name,' . $id,
            'alias' => 'required|unique:mitra,alias,' . $id
        ];

        $messages = [
            'name.required'  => 'Nama tidak boleh kosong',
            'name.unique'    => 'Nama sudah ada',
            'alias.required' => 'Alias tidak boleh kosong',
            'alias.unique'   => 'Alias sudah ada',
        ];

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }
}
