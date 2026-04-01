<?php

namespace App\Mail;

use App\Models\Asset;
use App\Models\Threat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThreatAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asset;
    public $threat;

    public function __construct(Asset $asset, Threat $threat)
    {
        $this->asset = $asset;
        $this->threat = $threat;
    }

    public function build()
    {
        return $this->subject('Nova Ameaça Identificada: ' . $this->asset->name)
                    ->view('emails.threat_added');
    }
}