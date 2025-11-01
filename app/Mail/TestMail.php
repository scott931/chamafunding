<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
	use Queueable, SerializesModels;

	public string $messageBody;

	public function __construct(string $messageBody = 'This is a test email from CrowdFunding.')
	{
		$this->messageBody = $messageBody;
	}

	public function build(): self
	{
		return $this
			->subject('CrowdFunding Mail Test')
			->view('emails.test');
	}
}
