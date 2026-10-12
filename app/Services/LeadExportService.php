<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Packs everything about one lead into a ZIP:
 *  - lead-details.csv : lead, customer, addresses, professional details, lenders, status history, documents
 *  - documents/lead/ and documents/customer/ : every uploaded file that is still on disk
 *  - README.txt        : lists any document whose file is missing from storage
 */
class LeadExportService
{
    public function build(Lead $lead): string
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'ZIP support (php-zip) is not enabled on this server.');

        $lead->load([
            'customer.addresses',
            'customer.professionalDetails',
            'customer.documents.documentType',
            'documents.documentType',
            'lenders.lender',
            'lenders.lenderProduct',
            'statusHistories.leadStatus',
            'statusHistories.changedBy',
            'source', 'category', 'subcategory', 'product', 'employmentType', 'leadStatus', 'assignee', 'creator',
        ]);

        $dir = storage_path('app/exports');
        File::ensureDirectoryExists($dir);

        $zipPath = $dir.'/'.$lead->lead_code.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';

        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Could not create the ZIP file.');

        $zip->addFromString('lead-details.csv', $this->csv($lead));

        $missing = [];
        $included = 0;

        $sources = [
            'lead' => $lead->documents,
            'customer' => $lead->customer?->documents ?? collect(),
        ];

        foreach ($sources as $folder => $documents) {
            foreach ($documents as $document) {
                $disk = Storage::disk($document->disk);

                if (! $disk->exists($document->path)) {
                    $missing[] = $document->id.' | '.$document->typeName().' | '.$document->original_name;
                    continue;
                }

                $name = $document->id.'-'.Str::slug(pathinfo($document->original_name, PATHINFO_FILENAME)).'.'.strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));
                $entry = 'documents/'.$folder.'/'.Str::slug($document->typeName()).'/'.$name;

                if ($document->disk === 'local') {
                    $zip->addFile($disk->path($document->path), $entry);
                } else {
                    $zip->addFromString($entry, $disk->get($document->path));
                }

                $included++;
            }
        }

        $readme = "Lead: {$lead->lead_code}\nExported: ".now()->format('d M Y H:i')." (IST)\nDocuments included: {$included}\n";
        $readme .= $missing
            ? "\nDocuments listed in the database but missing on the server disk:\n".implode("\n", $missing)."\n"
            : "\nAll uploaded documents were included.\n";

        $zip->addFromString('README.txt', $readme);
        $zip->close();

        return $zipPath;
    }

    protected function csv(Lead $lead): string
    {
        $rows = [['Section', 'Field', 'Value']];

        $add = function (string $section, array $attributes, ?string $prefix = null) use (&$rows) {
            foreach ($attributes as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $rows[] = [$section, ($prefix ? $prefix.' ' : '').$key, (string) $value];
            }
        };

        $add('Lead', $lead->getAttributes());
        $rows[] = ['Lead', 'Status (name)', (string) $lead->leadStatus?->name];
        $rows[] = ['Lead', 'Product (name)', (string) $lead->product?->name];
        $rows[] = ['Lead', 'Category (name)', (string) $lead->category?->name];
        $rows[] = ['Lead', 'Subcategory (name)', (string) $lead->subcategory?->name];
        $rows[] = ['Lead', 'Source (name)', (string) $lead->source?->name];
        $rows[] = ['Lead', 'Assigned to', (string) $lead->assignee?->name];
        $rows[] = ['Lead', 'Created by', (string) $lead->creator?->name];

        if ($customer = $lead->customer) {
            $add('Customer', $customer->getAttributes());

            foreach ($customer->addresses as $i => $address) {
                $add('Customer address '.($i + 1), $address->getAttributes());
            }

            foreach ($customer->professionalDetails as $i => $detail) {
                $add('Professional details '.($i + 1), $detail->getAttributes());
            }
        }

        foreach ($lead->lenders as $i => $selection) {
            $rows[] = ['Lender '.($i + 1), 'Lender', (string) $selection->lender?->name];
            $rows[] = ['Lender '.($i + 1), 'Product', (string) $selection->lenderProduct?->product_name];
            $add('Lender '.($i + 1), collect($selection->getAttributes())->except(['id', 'lead_id'])->all());
        }

        foreach ($lead->statusHistories as $history) {
            $rows[] = [
                'Status history',
                $history->created_at?->format('d M Y H:i') ?? '',
                trim(($history->leadStatus?->name ?? '').' '.($history->note ?? '')).' by '.($history->changedBy?->name ?? '-'),
            ];
        }

        foreach ($lead->documents as $document) {
            $rows[] = ['Lead document', $document->typeName(), $document->original_name.' | status: '.$document->status.' | uploaded: '.$document->created_at?->format('d M Y H:i')];
        }

        foreach ($lead->customer?->documents ?? [] as $document) {
            $rows[] = ['Customer document', $document->typeName(), $document->original_name.' | status: '.$document->status.' | uploaded: '.$document->created_at?->format('d M Y H:i')];
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Hindi/Unicode correctly
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
