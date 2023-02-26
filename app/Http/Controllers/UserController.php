<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UserImport;
use App\Services\UserService;

class UserController extends Controller
{

    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }


    /**
     * Upload user by excel 
     *
     */
    public function upload(Request $request)
    {
        $file = $request->file('file');
        try {
            Excel::import(new UserImport, $file, \Maatwebsite\Excel\Excel::XLSX);
            return response()->json(['message' => "Success Upload user"], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {

        $users = User::where('is_active', true)->with(['regional', 'witel', 'mitra'])->orderBy('name', 'ASC');

        if ($request->regional_id)
            $users->where('regional_id', $request->regional_id);

        if ($request->mitra_id)
            $users->where('mitra_id', $request->mitra_id);

        return $users->get();
    }


    /**
     * 
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {
        try {
            $validate = $this->userService->validate($request);

            if (is_object($validate))
                throw new \Exception($validate);

            $this->userService->create($request);

            return response()->json(['message' => 'Berhasil membuat user'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, User $user)
    {
        try {
            $validate = $this->userService->validate($request, $user);
            if (is_object($validate))
                throw new \Exception($validate);

            $user = $this->userService->update($request, $user);

            return response()->json(['message' => 'Berhasil memperbaharui data user', 'user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $this->messageError($e)], 400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy(User $user)
    {
        $userService = $this->userService->delete($user);

        if (!$userService)
            return response()->json(['message' => 'Gagal menghapus data user'], 400);

        return response()->json(['message' => 'Berhasil menghapus data user'], 200);
    }


    /**
     * Change password  
     *
     */
    public function changePassword(Request $request)
    {
        $userService = $this->userService->changePassword($request);

        if (!$userService)
            return response()->json(['message' => 'Gagal mengganti password'], 400);

        return response()->json(['message' => 'Berhasil mengganti password'], 200);
    }


    /**
     * Change update photo 
     *
     */
    public function updatePhoto(Request $request)
    {
        $user = $this->userService->changePhotoProfile($request);

        if (!$user)
            return response()->json(['message' => 'Gagal mengganti foto'], 400);

        return response()->json(['message' => 'Berhasil mengganti foto', 'user' => $user], 200);
    }
}
