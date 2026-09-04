<?php

namespace App\Notifications;

use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewPackageAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public $package;

    /**
     * Create a new notification instance.
     */
    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // We only want to save it to DB for the bell
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nuevo paquete asignado',
            'message' => "El paquete {$this->package->package_reference} ha sido asignado a tu ruta.",
            'reference_id' => $this->package->id,
            'type' => 'package_assignment'
        ];
    }
}
