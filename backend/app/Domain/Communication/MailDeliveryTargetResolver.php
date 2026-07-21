<?php

namespace App\Domain\Communication;

class MailDeliveryTargetResolver
{
	public function safetyRecipient(): ?string
	{
		$recipient = trim((string) config('services.mail_safety.redirect_all_to', ''));

		return $recipient !== '' ? $recipient : null;
	}

	/**
	 * @return array{
	 *     recipient: string,
	 *     metadata: array{delivery_mode: string, intended_recipient: string, safety_recipient?: string}
	 * }
	 */
	public function resolve(string $recipient): array
	{
		$safetyRecipient = $this->safetyRecipient();

		if ($safetyRecipient === null) {
			return [
				'recipient' => $recipient,
				'metadata' => [
					'delivery_mode' => 'direct',
					'intended_recipient' => $recipient,
				],
			];
		}

		if (strcasecmp($recipient, $safetyRecipient) === 0) {
			return [
				'recipient' => $recipient,
				'metadata' => [
					'delivery_mode' => 'direct_to_safety_address',
					'intended_recipient' => $recipient,
					'safety_recipient' => $safetyRecipient,
				],
			];
		}

		return [
			'recipient' => $safetyRecipient,
			'metadata' => [
				'delivery_mode' => 'redirected_to_safety_address',
				'intended_recipient' => $recipient,
				'safety_recipient' => $safetyRecipient,
			],
		];
	}
}