<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmAssignmentNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $sort = in_array($request->string('sort')->toString(), ['title', 'priority', 'status', 'due_date'], true)
            ? $request->string('sort')->toString()
            : 'due_date';

        return Inertia::render('Tasks/Index', [
            'tasks' => $workspace->tasks()->with('assignedTo:id,name')->orderByRaw("status = 'completed'")->orderBy($sort)->paginate(20)->withQueryString(),
            'members' => $workspace->members()->select('users.id', 'name')->get(),
        ]);
    }

    public function store(StoreTaskRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $workspace = $request->attributes->get('workspace');
        $task = Task::create(['workspace_id' => $workspace->id, 'created_by_id' => $request->user()->id] + $request->validated());
        $logger->log($workspace, 'task.created', "Task {$task->title} created.", $task);
        User::find($task->assigned_to_id)?->notify(new CrmAssignmentNotification('New task assigned', $task->title, route('tasks.index')));

        return back()->with('success', 'Task created.');
    }

    public function update(StoreTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $task->update($request->validated());

        return back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return back()->with('success', 'Task deleted.');
    }
}
