<?php

namespace App\Http\Controllers;

use App\Models\AttributeCategory;
use Illuminate\Http\Request;

class AttributeCategoryController extends Controller
{
    public function index()
    {
        $attributeCategories = AttributeCategory::all();
        return response()->json($attributeCategories);
    }
}
