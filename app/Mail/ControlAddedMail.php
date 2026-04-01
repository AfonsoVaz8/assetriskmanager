<?php

namespace App\Mail;

use App\Models\Asset;
use App\Models\Control;
use App\Models\Threat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ControlAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asset;
    public $control;
    public $threat;

    public function __construct(Asset $asset, Control $control, Threat $threat)
    {
        $this->asset = $asset;
        $this->control = $control;
        $this->threat = $threat;
    }

    public function build()
    {
        return $this->subject('Novo Controlo Aplicado: ' . $this->asset->name)
                    ->view('emails.control_added');
    }
}