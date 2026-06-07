<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAi\EventAssistantService;
use App\Services\ReportAi\ReportAiRuntimeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventAssistantController extends Controller
{
    public function index(): View
    {
        return view('admin.event-assistant.index', [
            'activeNav' => 'event_assistant',
            'assistantConfigured' => ReportAiRuntimeSettings::resolve()->isUsable(),
        ]);
    }

    public function run(Request $request, EventAssistantService $assistant): JsonResponse
    {
        $maxLen = max(200, min(8000, (int) config('report_ai.max_question_length', 900)));
        $validated = $request->validate([
            'instruction' => ['required', 'string', 'max:'.$maxLen],
        ]);

        return response()->json($assistant->run($validated['instruction']));
    }
}
