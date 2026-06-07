<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportAi\NaturalLanguageReportService;
use App\Services\ReportAi\ReportAiRuntimeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class ReportAiController extends Controller
{
    public function index(): View
    {
        return view('admin.report-ai.index', [
            'activeNav' => 'report_ai',
            'reportAiConfigured' => ReportAiRuntimeSettings::resolve()->isUsable(),
        ]);
    }

    public function query(Request $request, NaturalLanguageReportService $nlp): JsonResponse
    {
        $maxLen = max(120, min(2000, (int) Config::get('report_ai.max_question_length', 900)));

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:'.$maxLen],
        ]);

        return response()->json($nlp->run($validated['question']));
    }
}
