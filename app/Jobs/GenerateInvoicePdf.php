<?php

namespace App\Jobs;

use App\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf implements ShouldQueue
{
    use Queueable;

    public Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Załadowanie danych zamówienia
        $order = $this->order->load(['items', 'user', 'coupon']);

        // Konfiguracja Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        // Przygotowanie HTML faktury
        $html = view('invoices.order', [
            'order' => $order,
        ])->render();

        // Generowanie PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Zapisywanie PDF
        $pdfPath = 'invoices/' . $order->order_number . '_' . date('Y-m-d') . '.pdf';
        Storage::disk('local')->put($pdfPath, $dompdf->output());

        // Aktualizacja zamówienia o ścieżkę do faktury (można dodać pole invoice_path w migracji)
        // $order->update(['invoice_path' => $pdfPath]);

        // Logowanie sukcesu
        \Log::info("Invoice PDF generated for order {$order->order_number}", [
            'path' => $pdfPath,
        ]);
    }
}
