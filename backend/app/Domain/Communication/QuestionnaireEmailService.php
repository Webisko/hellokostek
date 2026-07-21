<?php

namespace App\Domain\Communication;

use App\Mail\QuestionnaireAdminNotificationMail;
use App\Mail\QuestionnaireResultMail;
use App\Models\QuestionnaireSubmission;
use App\Models\TransactionalEmailLog;
use App\Support\StoreSettings;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class QuestionnaireEmailService
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
		private readonly MailDeliveryTargetResolver $mailDeliveryTargetResolver,
    ) {
    }

    public function sendSubmissionEmails(QuestionnaireSubmission $submission): void
    {
        $resultEmailStatus = $this->deliver(
            emailType: 'questionnaire_result_customer',
            recipient: $submission->email,
            submission: $submission,
            mailable: new QuestionnaireResultMail($submission),
        );

        $adminEmail = $this->storeSettings->adminNotificationEmail();

        $adminNotificationStatus = filled($adminEmail)
            ? $this->deliver(
                emailType: 'questionnaire_result_admin',
                recipient: (string) $adminEmail,
                submission: $submission,
                mailable: new QuestionnaireAdminNotificationMail($submission),
            )
            : 'skipped';

        $submission->forceFill([
            'result_email_status' => $resultEmailStatus,
            'admin_notification_status' => $adminNotificationStatus,
        ])->save();
    }

    private function deliver(string $emailType, string $recipient, QuestionnaireSubmission $submission, Mailable $mailable): string
    {
		$deliveryTarget = $this->mailDeliveryTargetResolver->resolve($recipient);
		$deliveryMetadata = $deliveryTarget['metadata'];

        $log = TransactionalEmailLog::query()->create([
            'order_id' => null,
            'email_type' => $emailType,
			'recipient' => $deliveryTarget['recipient'],
            'subject' => (string) $mailable->envelope()->subject,
            'status' => 'pending',
            'payload' => [
                'questionnaire_submission_id' => $submission->id,
                'questionnaire_key' => $submission->questionnaire_key,
                'customer_email' => $submission->email,
                'coupon_code' => $submission->coupon_code,
                'recommended_products' => $submission->recommended_products,
            ],
			'metadata' => $deliveryMetadata,
        ]);

        try {
			Mail::to($deliveryTarget['recipient'])->send($mailable);

            $log->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
				'metadata' => array_merge($deliveryMetadata, [
                    'mailer' => config('mail.default'),
				]),
            ])->save();

            return 'sent';
        } catch (Throwable $exception) {
            $log->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
				'metadata' => array_merge($deliveryMetadata, [
                    'mailer' => config('mail.default'),
                    'exception' => get_class($exception),
				]),
            ])->save();

            return 'failed';
        }
    }
}