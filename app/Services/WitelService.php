<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\Witel;

class WitelService
{

    /**
     * Create
     */
    public function create($request)
    {
        try {

            $payload = $request->only('name', 'alias', 'regional_id');
            $model = Witel::make($payload);
            $model->manager_id = $request->manager_id;
            return $model->save();

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Update
     */
    public function update($request, $witel)
    {
        try {
            $witel->name        = $request->name;
            $witel->alias       = $request->alias;
            $witel->regional_id = $request->regional_id;
            $witel->manager_id  = $request->manager_id;
            $witel->update();

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
            'regional_id'  => 'required',
            'name'         => 'required|unique:witel,name,' . $id,
            'alias'        => 'unique:witel,alias,' . $id
        ];

        $messages = [
            'regional.required'  => 'Regional tidak boleh kosong',
            'name.required'      => 'Nama tidak boleh kosong',
            'name.unique'        => 'Nama sudah ada',
            'alias.unique'       => 'Alias sudah ada',
        ];

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }
}
