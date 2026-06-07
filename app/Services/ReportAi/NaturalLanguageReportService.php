<?php

declare(strict_types=1);

namespace App\Services\ReportAi;

use Illuminate\Support\Facades\Config;

final class NaturalLanguageReportService
{
    public function __construct(
        private ReportAiCompletionClient $sqlGenerator,
        private SafeReportSelectExecutor $executor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(string $question): array
    {
        $question = trim($question);
        $maxQuestion = max(120, min(2000, (int) Config::get('report_ai.max_question_length', 900)));

        if (mb_strlen($question) === 0) {
            return ['ok' => false, 'error' => 'Ask a reporting question before running the assistant.'];
        }

        $runtime = ReportAiRuntimeSettings::resolve();
        if (! $runtime->isUsable()) {
            return ['ok' => false, 'error' => $runtime->unusableReason()];
        }

        if (mb_strlen($question) > $maxQuestion) {
            return ['ok' => false, 'error' => 'Question is too long. Shorten it to '.$maxQuestion.' characters or fewer.'];
        }

        $generated = $this->sqlGenerator->generate($question);
        $generationError = (string) ($generated['error'] ?? '');
        if ($generationError !== '') {
            return [
                'ok' => false,
                'error' => $generationError,
                'summary' => $generated['summary'] ?? null,
            ];
        }

        $sqlForRun = isset($generated['sql']) ? trim((string) $generated['sql']) : '';
        if ($sqlForRun === '') {
            return ['ok' => false, 'error' => 'The model did not return SQL.'];
        }

        $executed = $this->executor->run($sqlForRun);
        $executionError = (string) ($executed['error'] ?? '');
        if ($executionError !== '') {
            return [
                'ok' => false,
                'error' => $executionError,
                'summary' => $generated['summary'] ?? null,
                'sql_attempted' => $sqlForRun,
            ];
        }

        return [
            'ok' => true,
            'summary' => $generated['summary'] ?? '',
            'sql' => (string) ($executed['sql'] ?? $sqlForRun),
            'columns' => $executed['columns'] ?? [],
            'rows' => $executed['rows'] ?? [],
            'truncated' => ! empty($executed['truncated']),
        ];
    }
}
