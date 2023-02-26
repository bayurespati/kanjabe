<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\Regional;

class RegionalService
{

    /**
     * Create
     */
    public function create($request)
    {
        try {

            $payload = $request->only('name', 'alias');
            $model = Regional::make($payload);
            return $model->save();

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Update
     */
    public function update($request, $regional)
    {
        try {
            $regional->name    = $request->name;
            $regional->alias   = $request->alias;
            $regional->update();

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
            'name'  => 'required|unique:regional,name,' . $id,
            'alias' => 'required|unique:regional,alias,' . $id
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
