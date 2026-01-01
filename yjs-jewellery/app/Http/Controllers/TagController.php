<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc') === 'true';
        $direction = $sortDesc ? 'DESC' : 'ASC';

        $sortBy = !empty($sortBy) ? $sortBy : 'id';

        $query = Tag::select(
            'tags.*',
            DB::raw('count(*) OVER() AS total_row_count')
        )
            ->when($request->name, function ($q) use ($request) {
                $q->where('tags.name', 'like', '%' . $request->name . '%');
            })
             ->when($request->description, function ($q) use ($request) {
                $q->where('tags.description', 'like', '%' . $request->description . '%');
            })
            ->when($request->globalSearch, function ($q) use ($request) {
                $q->where('tags.name', 'like', '%' . $request->globalSearch . '%')
                    ->orWhere('tags.description', 'like', '%' . $request->globalSearch . '%');
            })
            ->orderBy($sortBy, $direction);

        if ($request->has('page') && $request->has('perPage')) {
            $page = (int)$request->page;
            $perPage = (int)$request->perPage;

            $data = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
        } else {
            $data = $query->get();
        }

        return response([
            'data' => $data,
            'status' => 'success'
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $existing = Tag::where('name', $request->name)
                ->whereNull('deleted_at')
                ->exists();

            if ($existing) {
                return response([
                    'errors' => ['name' => ['The name has already been taken.']],
                    'code' => 422
                ], 422);
            }
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'regex:/^[A-Za-z ]+$/',
                    Rule::unique('tags', 'name')->whereNull('deleted_at'),
                ],
            ], [
                'name.required' => 'Please enter a tag name.',
                'name.regex' => 'Tag name must contain alphabets only.',
                'name.unique' => 'The name has already been taken.',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $tag = new Tag();
            $tag->name = $request->name;
            $tag->description = $request->description;
            $tag->status = $request->status ?? 'A';
            $tag->save();


            return response([
                'status' => 'success',
                'data' => $tag
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $existing = Tag::where('name', $request->name)
                ->where('id', '!=', $id)
                ->whereNull('deleted_at')
                ->exists();

            if ($existing) {
                return response([
                    'errors' => ['name' => ['The name has already been taken.']],
                    'code' => 422
                ], 422);
            }
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'regex:/^[A-Za-z ]+$/',
                    Rule::unique('tags', 'name')
                        ->ignore($id)
                        ->whereNull('deleted_at'),
                ],
            ], [
                'name.required' => 'Please enter a tag name.',
                'name.regex' => 'Tag name must contain alphabets only.',
                'name.unique' => 'The name has already been taken.',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }
            
            $tag = Tag::find($id);

            if (!$tag) {
                return response([
                    'error' => 'Tag not found.',
                    'code' => 404
                ], 404);
            }

            $tag->name = $request->name;
            $tag->description = $request->description;
            $tag->status = $request->status ?? 'A';
            $tag->save();
            

            return response([
                'data' => 'Updated Successfully!',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();
        return response()->json(['status' => 'deleted', 'message' => 'Deleted Successfully!'], 200);
    }

    public function changeStatus($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->status = $tag->status === 'A' ? 'I' : 'A';
        $tag->save();
        $statusText = $tag->status === 'A' ? 'Active' : 'Inactive';
        return response()->json([
            'message' => 'Status changed to ' . $statusText,
            'status' => 'success'
        ]);
    }
}
