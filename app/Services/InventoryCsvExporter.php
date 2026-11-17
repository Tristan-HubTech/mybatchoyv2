<?php

namespace App\Services;

use App\Models\InventoryItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryCsvExporter
{
    public static function sanitizeCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');
        if ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $cell;
        }

        return $cell;
    }

    public static function streamInventoryReport(): StreamedResponse
    {
        $fileName = 'mybatchoy_inventory_report_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Item Name', 'Category', 'Unit', 'Current Qty', 'Min Stock', 'Reorder Level', 'Status']);

            InventoryItem::with(['category:id,name', 'unit:id,short_name'])
                ->orderBy('id')
                ->chunk(200, function ($items) use ($handle): void {
                    foreach ($items as $item) {
                        $status = 'Normal';
                        if ($item->current_quantity <= 0) {
                            $status = 'Out of Stock';
                        } elseif ($item->current_quantity <= $item->minimum_stock_level) {
                            $status = 'Low Stock';
                        }

                        fputcsv($handle, [
                            self::sanitizeCell($item->sku),
                            self::sanitizeCell($item->name),
                            self::sanitizeCell($item->category?->name ?? 'N/A'),
                            self::sanitizeCell($item->unit?->short_name ?? 'N/A'),
                            $item->current_quantity,
                            $item->minimum_stock_level,
                            $item->reorder_level,
                            $status,
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}

