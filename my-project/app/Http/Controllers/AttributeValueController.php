<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index()
    {
        $attributeValues = AttributeValue::all();
        return response()->json($attributeValues);
    }
}
