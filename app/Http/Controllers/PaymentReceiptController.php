<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;

class PaymentReceiptController extends Controller
{
    public function newDownloadPaymentReceipts(int $projectId, int $transactionId)
    {
        $transaction = Transaction::with('customer', 'unit')->findOrFail($transactionId);
        $project = Project::findOrFail($projectId);

        if (auth()->user()->organisation_id !== $project->organisation_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $fileName = Str::slug((string) ($transaction->receipt_number ?: 'receipt-'.$transaction->id));

            return Pdf::view('receipt_BS', [
                'project' => $project,
                'transaction' => $transaction,
                'projectLogoSrc' => $this->resolveProjectLogoSrc($project),
            ])
                ->driver('cloudflare')
                ->format('a4')
                ->name($fileName.'.pdf')
                ->download();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to generate PDF: '.$e->getMessage()], 500);
        }
    }

    private function resolveProjectLogoSrc(Project $project): ?string
    {
        $rawPath = $project->getRawOriginal('logo');

        if (blank($rawPath)) {
            return null;
        }

        $normalizedPath = Str::of($rawPath)
            ->replace('\\', '/')
            ->ltrim('/')
            ->value();

        $storageRelativePath = ltrim(Str::replaceFirst('storage/', '', $normalizedPath), '/');
        $storageFilePath = storage_path('app/public/'.$storageRelativePath);

        if (is_file($storageFilePath)) {
            return $this->fileToDataUri($storageFilePath);
        }

        $publicFilePath = public_path($normalizedPath);

        if (is_file($publicFilePath)) {
            return $this->fileToDataUri($publicFilePath);
        }

        if (filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            return $normalizedPath;
        }

        return asset($normalizedPath);
    }

    private function fileToDataUri(string $filePath): ?string
    {
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
