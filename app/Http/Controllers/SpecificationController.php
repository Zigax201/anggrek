<?php

namespace App\Http\Controllers;

use App\Models\Specification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SpecificationController extends Controller
{
    public function show_spec_by_id(Request $request)
    {
        return response([
            'message' => 'Success get spec by id '.$request->id_spec,
            'spec' => Specification::find($request->id_spec)
        ]);
    }

    public function show_spec_by_product(Request $request)
    {
        return response([
            'message' => 'Success get spec',
            'spec' => Specification::where('id_product', $request->id_product)->get()
        ]);
    }

    public function insert_spec(Request $request)
    {
        if (Auth::user()->role == 1) {
            Specification::create([
                'id_product' => $request->id_product,
                'name_spec' => $request->name_spec,
                'base_price' => $request->base_price,
                'publish_price' => $request->publish_price
            ]);
            return response(['message' => 'Success insert Spec in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }

    public function update_spec(Request $request)
    {
        if (Auth::user()->role == 1) {
            $spec = Specification::find($request->id_spec);
            $spec->toQuery()->update([
                'id_product' => $request->id_product,
                'name_spec' => $request->name_spec,
                'base_price' => $request->base_price,
                'publish_price' => $request->publish_price
            ]);
            return response(['message' => 'Success Update Spec in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }
    
    public function delete_spec(Request $request)
    {
        if (Auth::user()->role == 1) {
            Specification::find($request->id_spec)->delete();
            return response(['message' => 'Success Delete Spec in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }
}
