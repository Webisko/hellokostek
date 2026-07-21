<?php

namespace App\Http\Controllers\Api;

use App\Domain\Communication\QuestionnaireEmailService;
use App\Domain\Questionnaires\QuestionnaireResultResolver;
use App\Http\Controllers\Controller;
use App\Models\QuestionnaireSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionnaireSubmissionController extends Controller
{
    public function __invoke(
        Request $request,
        QuestionnaireEmailService $questionnaireEmailService,
        QuestionnaireResultResolver $questionnaireResultResolver,
    ): JsonResponse
    {
        $questionnaireKey = (string) ($request->input('questionnaire_key') ?: 'mushroom-matcher');

        $validated = $request->validate([
            'questionnaire_key' => ['nullable', 'string', 'max:120', Rule::in($questionnaireResultResolver->supportedQuestionnaireKeys())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'source' => ['nullable', 'string', 'max:120'],
            'consented_to_marketing' => ['sometimes', 'boolean'],
            'answers' => ['required', 'array', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ]);

        $consentedToMarketing = (bool) ($validated['consented_to_marketing'] ?? false);
        $questionnaireResult = $questionnaireResultResolver->resolve($questionnaireKey, $validated['answers']);

        $submission = QuestionnaireSubmission::query()->create([
            'questionnaire_key' => $questionnaireResult['questionnaire_key'],
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'source' => $validated['source'] ?? 'api.questionnaire.submit',
            'consented_to_marketing' => $consentedToMarketing,
            'consented_at' => $consentedToMarketing ? now() : null,
            'answers' => $validated['answers'],
            'recommended_products' => $questionnaireResult['recommendations'],
            'coupon_code' => $questionnaireResult['coupon_code'],
            'result_email_status' => 'pending',
            'admin_notification_status' => 'pending',
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $questionnaireEmailService->sendSubmissionEmails($submission);
        $submission->refresh();

        return response()->json([
            'data' => [
                'submission' => [
                    'id' => $submission->id,
                    'questionnaire_key' => $submission->questionnaire_key,
                    'email' => $submission->email,
                    'recommended_products' => $submission->recommended_products,
                    'coupon_code' => $submission->coupon_code,
                    'result_email_status' => $submission->result_email_status,
                    'admin_notification_status' => $submission->admin_notification_status,
                    'created_at' => optional($submission->created_at)->toIso8601String(),
                ],
                'result' => $questionnaireResult,
            ],
        ], 201);
    }
}