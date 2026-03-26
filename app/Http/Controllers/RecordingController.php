<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RecordingController extends Controller
{
    public function index(): View
    {
        return view('recordings.index', [
            'recordings' => Recording::query()
                ->with('camera')
                ->latest()
                ->paginate(20),
            'cameras' => Camera::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Recording $recording): View
    {
        return view('recordings.show', [
            'recording' => $recording,
            'recordingUrl' => route('recordings.file', $recording),
        ]);
    }

    public function destroy(Recording $recording): RedirectResponse
    {
        if (Storage::disk('public')->exists($recording->file_path)) {
            Storage::disk('public')->delete($recording->file_path);
        }

        $recording->delete();

        return redirect()->route('recordings.index')->with('status', 'Recording deleted successfully.');
    }

    public function file(Recording $recording)
    {
        abort_unless(Storage::disk('public')->exists($recording->file_path), 404);

        return Storage::disk('public')->response($recording->file_path, basename($recording->file_path), [
            'Content-Type' => 'video/mp4',
        ]);
    }
}
