<?php

namespace App\Http\Controllers;

use App\Models\Information;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class InformationController extends Controller
{
    public function show_info_by_id(Request $request)
    {
        return response([
            'message' => 'Success get info by id '.$request->id_info,
            'info' => Information::find($request->id_info)
        ]);
    }

    public function show_info_by_product(Request $request)
    {
        return response([
            'message' => 'Success get info',
            'info' => Information::where('id_product', $request->id_product)->get()
        ]);
    }

    public function insert_info(Request $request)
    {
        if (Auth::user()->role == 1) {
            Information::create([
                'id_product' => $request->id_product,
                'id_catalog' => $request->id_catalog,
                'parameter' => $request->parameter,
                'value' => $request->value
            ]);
            return response(['message' => 'Success insert info in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }

    public function update_info(Request $request)
    {
        if (Auth::user()->role == 1) {
            $info = Information::find($request->id_info);
            $info->toQuery()->update([
                'id_product' => $request->id_product,
                'id_catalog' => $request->id_catalog,
                'parameter' => $request->parameter,
                'value' => $request->value
            ]);
            return response(['message' => 'Success Update info in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }
    
    public function delete_info(Request $request)
    {
        if (Auth::user()->role == 1) {
            Information::find($request->id_info)->delete();
            return response(['message' => 'Success Delete info in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }
}
