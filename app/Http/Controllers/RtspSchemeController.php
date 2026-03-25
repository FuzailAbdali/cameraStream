<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRtspSchemeRequest;
use App\Http\Requests\UpdateRtspSchemeRequest;
use App\Models\RtspScheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RtspSchemeController extends Controller
{
    public function index(): View
    {
        return view('rtsp-schemes.index', [
            'rtspSchemes' => RtspScheme::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('rtsp-schemes.create');
    }

    public function store(StoreRtspSchemeRequest $request): RedirectResponse
    {
        RtspScheme::query()->create($request->validated());

        return redirect()->route('rtsp-schemes.index')->with('status', 'RTSP scheme created successfully.');
    }

    public function edit(RtspScheme $rtsp_scheme): View
    {
        return view('rtsp-schemes.edit', [
            'rtspScheme' => $rtsp_scheme,
        ]);
    }

    public function update(UpdateRtspSchemeRequest $request, RtspScheme $rtsp_scheme): RedirectResponse
    {
        $rtsp_scheme->update($request->validated());

        return redirect()->route('rtsp-schemes.index')->with('status', 'RTSP scheme updated successfully.');
    }

    public function destroy(RtspScheme $rtsp_scheme): RedirectResponse
    {
        $rtsp_scheme->delete();

        return redirect()->route('rtsp-schemes.index')->with('status', 'RTSP scheme deleted successfully.');
    }
}
