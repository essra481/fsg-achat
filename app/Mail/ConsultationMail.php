<?php

namespace App\Mail;

use App\Models\consultation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;

class ConsultationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $consultation;

    public function __construct(consultation $consultation)
    {
        $this->consultation = $consultation;
    }

    public function build()
    {
        $pdf = Pdf::loadView(
            'consultation.pdf',
            ['consultation' => $this->consultation]
        );

        return $this->subject('Consultation N° '.$this->consultation->reference)
            ->view('emails.consultation')
            ->attachData(
                $pdf->output(),
                'Consultation_'.$this->consultation->reference.'.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}