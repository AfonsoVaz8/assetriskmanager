<?php

namespace App\Mail;

use App\Models\Asset;
use App\Models\Control;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ControlAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asset;
    public $control;

    public function __construct(Asset $asset, Control $control)
    {
        $this->asset = $asset;
        $this->control = $control;
    }

    public function build()
    {
        return $this->subject('Novo Controlo Aplicado: ' . $this->asset->name)
                    ->view('emails.control_added');
    }
}