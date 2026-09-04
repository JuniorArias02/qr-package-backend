<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DriverReportedIssue extends Notification implements ShouldQueue
{
    use Queueable;

    public $delivery;

    /**
     * Create a new notification instance.
     */
    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $driverName = $this->delivery->driver && $this->delivery->driver->user 
            ? $this->delivery->driver->user->name 
            : 'Un conductor';
            
        $packageRef = $this->delivery->package ? $this->delivery->package->package_reference : 'N/A';

        return [
            'title' => 'Novedad Reportada',
            'message' => "{$driverName} ha reportado una novedad con el paquete {$packageRef}.",
            'reference_id' => $this->delivery->id,
            'type' => 'driver_issue'
        ];
    }
}
