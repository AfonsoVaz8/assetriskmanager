<?php

namespace App\Mail;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AssetAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asset;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function build()
    {
        return $this->subject('Novo Ativo Atribuído: ' . $this->asset->name)
                    ->view('emails.asset_assigned'); 
    }
}