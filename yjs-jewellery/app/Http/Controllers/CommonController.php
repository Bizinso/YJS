<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\country;
use App\Models\state;
use App\Models\city;
use App\Models\Department;
use App\Models\Role;
use App\Models\Branch;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use App\Models\MetalType;
use App\Models\Attribute;
use App\Models\AdditionalCharges;
use App\Models\Tax;
use App\Models\ProductType;
use App\Models\Partner;

class CommonController extends Controller
{
    public function adminmenus()
    {
        try {
            $menu = Menu::whereNull('parent_id')
                ->orderBy('order', 'ASC')
                ->with('children')
                ->get();

            return response()->json([
                "status" => true,
                "message" => "Menu loaded successfully",
                "data" => $menu
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function getCountries()
    {
        $countries = country::select('id as value', 'name as label','phone_code')->get();
        $countryCodes = $countries->map(function ($item) {
            return [
                'value' => $item->phonecode,
                'label' => '+' . $item->phonecode,
            ];
        })->unique('value')->values();
        return response()->json(['data' => $countries,  'countryCodes' => $countryCodes,]);
    }

    public function getStates($country_id)
    {
        $id = base64_decode($country_id);

        $states = state::where('country_id', $id)
            ->select('id as value', 'name as label')
            ->get();
        return response()->json(['data' => $states]);
    }

    public function getCities($state_id)
    {
        $id = base64_decode($state_id);
        $cities = city::where('state_id', $id)
            ->select('id as value', 'name as label')
            ->get();
        return response()->json(['data' => $cities]);
    }

    public function getAllCounts()
    {
        try {
            $counts = [
                // Organization Structure
                'organization_structure' => [
                    'Branch' => Branch::count(),
                    'Department' => Department::count(),
                    'Role' => Role::count(),
                    'Employee' => User::where('user_type','employee')->count(),
                ],
                // Product Management
                'product_management' => [
                    'Category' => Category::whereNull('parent_id')->count(),
                    'Sub Category' => Category::whereNotNull('parent_id')->count(),
                    'Tag/Labels' => Tag::count(),
                    'Material/Metal Type' => MetalType::count(),
                    'Attributes' => Attribute::count(),
                    'Additional Charges ' => AdditionalCharges::count(),
                    'Tax' => Tax::count(),
                    'Product Type' => ProductType::count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $counts
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching counts: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAllPartners()
    {
        $partners = Partner::join('users', 'partners.user_id', '=', 'users.id')
            ->whereNull('partners.deleted_at')
            ->whereNull('users.deleted_at')
            ->where('users.user_type', 'partner')
            ->select(
                'users.id as id',
                'partners.business_name as name'
            )
            ->orderBy('partners.business_name')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Partners fetched successfully',
            'data'    => $partners
        ]);
    }

}
