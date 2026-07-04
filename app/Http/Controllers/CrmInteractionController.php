<?php

namespace App\Http\Controllers;

use App\Models\CrmFile;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailLog;
use App\Models\Lead;
use App\Models\Note;
use App\Services\AI\AIEmailSummaryService;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmInteractionController extends Controller
{
    public function note(Request $request, ActivityLogger $logger): RedirectResponse
    {
        [$record, $workspace] = $this->record($request);
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        Note::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'notable_type' => $record->getMorphClass(),
            'notable_id' => $record->getKey(),
            'body' => $validated['body'],
        ]);

        $logger->log($workspace, 'note.added', 'Note added to '.$this->label($record).'.', $record);

        return back()->with('success', 'Note added.');
    }

    public function email(Request $request, AIEmailSummaryService $summary, ActivityLogger $logger): RedirectResponse
    {
        [$record, $workspace] = $this->record($request);
        $validated = $request->validate([
            'direction' => ['required', 'in:sent,received'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'sender' => ['required', 'email'],
            'receiver' => ['required', 'email'],
        ]);

        EmailLog::create([
            ...$validated,
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'emailable_type' => $record->getMorphClass(),
            'emailable_id' => $record->getKey(),
            'summary' => $summary->summarize($validated['body']),
        ]);

        $logger->log($workspace, 'email.logged', 'Email logged for '.$this->label($record).'.', $record);

        return back()->with('success', 'Email logged.');
    }

    public function file(Request $request, ActivityLogger $logger): RedirectResponse
    {
        [$record, $workspace] = $this->record($request);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $uploaded = $validated['file'];
        $path = $uploaded->store("workspaces/{$workspace->id}", 'local');

        CrmFile::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'fileable_type' => $record->getMorphClass(),
            'fileable_id' => $record->getKey(),
            'name' => $uploaded->getClientOriginalName(),
            'mime_type' => $uploaded->getMimeType() ?? 'application/octet-stream',
            'size' => $uploaded->getSize(),
            'path' => $path,
        ]);

        $logger->log($workspace, 'file.uploaded', 'File uploaded to '.$this->label($record).'.', $record);

        return back()->with('success', 'File uploaded.');
    }

    public function download(Request $request, CrmFile $file): StreamedResponse
    {
        abort_unless($request->user()->workspaces()->whereKey($file->workspace_id)->exists(), 403);

        return Storage::disk('local')->download($file->path, $file->name);
    }

    private function record(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', 'in:lead,customer,deal'],
            'id' => ['required', 'integer'],
        ]);

        $model = match ($validated['type']) {
            'lead' => Lead::class,
            'customer' => Customer::class,
            'deal' => Deal::class,
        };

        $record = $model::findOrFail($validated['id']);
        $workspace = $request->attributes->get('workspace');
        abort_unless((int) $record->workspace_id === (int) $workspace->id, 403);

        return [$record, $workspace];
    }

    private function label(Model $record): string
    {
        return $record->name ?? $record->title ?? class_basename($record);
    }
}
