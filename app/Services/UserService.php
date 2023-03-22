<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class UserService
{

    /**
     * Create
     */
    public function create($request)
    {
        try {

            $payload = $request->only(
                'name',
                'email',
                'nik',
                'posisi',
                'direktorat',
                'witel_id',
                'phone',
                'regional_id',
                'role_id',
                'is_admin',
                'mitra_id',
            );

            $model = User::make($payload);
            $model->password = bcrypt("iota2022");
            return $model->save();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Update
     */
    public function update($request, $user)
    {
        try {
            $user->name        = $request->name;
            $user->email       = $request->email;
            $user->nik         = $request->nik;
            $user->direktorat  = $request->direktorat;
            $user->witel_id    = $request->witel_id;
            $user->phone       = $request->phone;
            $user->regional_id = $request->regional_id;
            $user->is_admin    = $request->is_admin;
            $user->mitra_id    = $request->mitra_id;
            $user->posisi      = $request->posisi;
            $user->update();

            return User::where('id', $user->id)->with(['witel', 'regional', 'mitra'])->first();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * Delete data
     */
    public function delete($model)
    {
        return $model->delete();
    }


    /**
     * Change password 
     */
    public function changePassword($request)
    {
        $model = Auth::user();
        $model->password = bcrypt($request->password);
        return $model->update();
    }


    /**
     * Change photo profile
     */
    public function changePhotoProfile($request)
    {
        $model = Auth::user();
        $model->photo = photoProfile($request, $model);
        $model->update();
        return User::where('id', $model->id)->with(['witel', 'regional', 'mitra'])->first();
    }


    /**
     * Validate input for check in
     */
    public function validate($request, $model = null)
    {

        $id = $model == null ? '' : $model->id;

        $validate = [
            'name'       => 'required',
            'email'      => 'required|unique:users,email,' . $id,
            'nik'        => 'required|unique:users,nik,' . $id,
            'phone'      => 'required|unique:users,phone,' . $id,
            'is_admin'   => 'required',
        ];

        $messages = [
            'name.required'        => 'Nama tidak boleh kosong',
            'email.required'       => 'Email tidak boleh kosong',
            'email.unique'         => 'Email sudah ada',
            'nik.required'         => 'NIK tidak boleh kosong',
            'nik.unique'           => 'NIK sudah ada',
            'phone.required'       => 'Phone tidak boleh kosong',
            'phone.unique'         => 'Phone sudah ada',
            'is_admin.required'    => 'Status admin tidak boleh kosong',
        ];

        if ($request->role_id == 4) {
            $validate['mitra_id'] = 'required';
            $messages['mitra_id.required'] = 'Mitra tidak boleh kosong';
        }

        if ($request->role_id == 2 || $request->role_id == 1) {
            $validate['regional_id'] = 'required';
            $messages['regional_id.required'] = 'Regional tidak boleh kosong';
        }

        if ($request->role_id == 1) {
            $validate['witel_id'] = 'required';
            $messages['witel_id.required'] = 'Witel tidak boleh kosong';
            $validate['posisi'] = 'required';
            $messages['posisi.required'] = 'Posisi tidak boleh kosong';
        }

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }
}
