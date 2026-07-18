<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Http\Resources\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('name_ar')->get();

        return ServiceResource::collection($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:100|unique:services,name_ar',
            'name_en' => 'required|string|max:100|unique:services,name_en',
        ]);

        $service = Service::create($validated);

        return new ServiceResource($service);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name_ar' => [
                'required',
                'string',
                'max:100',
                Rule::unique('services', 'name_ar')->ignore($service->id),
            ],
            'name_en' => [
                'required',
                'string',
                'max:100',
                Rule::unique('services', 'name_en')->ignore($service->id),
            ],
        ]);

        $service->update($validated);

        return new ServiceResource($service);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json(['message' => 'تم حذف الخدمة بنجاح']);
    }
}
