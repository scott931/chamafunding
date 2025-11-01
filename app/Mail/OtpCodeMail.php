<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
	use Queueable, SerializesModels;

	public string $code;
	public int $minutes;

	public function __construct(string $code, int $minutes)
	{
		$this->code = $code;
		$this->minutes = $minutes;
	}

	public function build(): self
	{
		return $this
			->subject('Your Verification Code')
			->view('emails.otp');
	}
}
