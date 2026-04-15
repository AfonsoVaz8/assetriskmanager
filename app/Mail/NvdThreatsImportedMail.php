<?php

namespace App\Mail;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NvdThreatsImportedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asset;
    public $threats;

    public function __construct(Asset $asset, array $threats)
    {
        $this->asset = $asset;
        $this->threats = $threats;
    }

    public function build()
    {
        return $this->subject('Vulnerabilidades Importadas (NVD): ' . $this->asset->name)
                    ->view('emails.nvd_threats_imported');
    }
}