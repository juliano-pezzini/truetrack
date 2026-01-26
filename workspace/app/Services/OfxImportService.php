<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use OfxParser\Parser;

class OfxImportService
{
    /**
     * Parse an OFX file and return the parsed data.
     *
     * @param  string  $filePath  Path to the OFX file
     *
     * @throws \Exception
     */
    public function parseOfxFile(string $filePath): \OfxParser\Ofx
    {
        if (! Storage::exists($filePath)) {
            throw new \Exception("OFX file not found: {$filePath}");
        }

        $content = Storage::get($filePath);
        $parser = new Parser();

        try {
            return $parser->loadFromString($content);
        } catch (\Exception $e) {
            throw new \Exception("Failed to parse OFX file: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Detect accounts present in the OFX file.
     *
     * @return array<int, array{accountNumber: string, accountType: string, bankId: string|null}>
     */
    public function detectAccounts(\OfxParser\Ofx $ofxData): array
    {
        $accounts = [];

        // @phpstan-ignore property.notFound
        foreach ($ofxData->bankAccounts as $bankAccount) {
            $accounts[] = [
                'accountNumber' => $bankAccount->accountNumber,
                'accountType' => $bankAccount->accountType,
                'bankId' => $bankAccount->routingNumber ?? null,
            ];
        }

        return $accounts;
    }

    /**
     * Compress and store the OFX file.
     *
     * @param  string  $originalPath  Temporary file path
     * @param  int  $accountId  Account ID
     * @param  int  $userId  User ID
     * @return array{path: string, hash: string}
     *
     * @throws \Exception
     */
    public function compressAndStoreFile(string $originalPath, int $accountId, int $userId): array
    {
        // Calculate SHA-256 hash of original file
        $fileHash = hash_file('sha256', $originalPath);

        // Create storage directory if it doesn't exist
        $directory = "ofx_imports/{$userId}/{$accountId}";
        Storage::makeDirectory($directory);

        // Generate unique filename with .gz extension
        $filename = date('Y-m-d_His').'_'.$fileHash.'.ofx.gz';
        $storagePath = "{$directory}/{$filename}";

        // Compress file using gzip
        $originalContent = file_get_contents($originalPath);
        $compressedContent = gzencode($originalContent, 9); // Maximum compression level

        if ($compressedContent === false) {
            throw new \Exception('Failed to compress OFX file');
        }

        // Store compressed file
        Storage::put($storagePath, $compressedContent);

        return [
            'path' => $storagePath,
            'hash' => $fileHash,
        ];
    }

    /**
     * Decompress a gzipped OFX file.
     *
     * @param  string  $compressedPath  Path to compressed file
     * @return string Path to decompressed file
     *
     * @throws \Exception
     */
    public function decompressFile(string $compressedPath): string
    {
        if (! Storage::exists($compressedPath)) {
            throw new \Exception("Compressed file not found: {$compressedPath}");
        }

        $compressedContent = Storage::get($compressedPath);
        $decompressedContent = gzdecode($compressedContent);

        if ($decompressedContent === false) {
            throw new \Exception('Failed to decompress OFX file');
        }

        // Create temporary decompressed file
        $tempPath = 'temp/'.uniqid('ofx_', true).'.ofx';
        Storage::put($tempPath, $decompressedContent);

        return $tempPath;
    }

    /**
     * Check if a file has already been imported.
     *
     * @param  string  $fileHash  SHA-256 hash of the file
     * @param  int  $accountId  Account ID
     * @return OfxImport|null The existing import or null if not found
     */
    public function checkDuplicateImport(string $fileHash, int $accountId): ?OfxImport
    {
        return OfxImport::where('file_hash', $fileHash)
            ->where('account_id', $accountId)
            ->whereIn('status', ['completed', 'processing'])
            ->first();
    }

    /**
     * Check if user has reached concurrent import limit.
     *
     * @param  int  $userId  User ID
     * @return bool True if limit is reached
     */
    public function checkConcurrencyLimit(int $userId): bool
    {
        $maxConcurrent = (int) Setting::getValue('max_concurrent_imports_per_user', 5);

        $activeImports = OfxImport::forUser($userId)
            ->active()
            ->count();

        return $activeImports >= $maxConcurrent;
    }

    /**
     * Extract transactions from parsed OFX data.
     *
     * @return array<int, array{amount: float, date: \Carbon\Carbon, description: string, type: string, fitId: string}>
     */
    public function extractTransactions(\OfxParser\Ofx $ofxData): array
    {
        $transactions = [];

        // @phpstan-ignore property.notFound
        foreach ($ofxData->bankAccounts as $bankAccount) {
            $statement = $bankAccount->statement;

            foreach ($statement->transactions as $transaction) {
                // Map OFX transaction type to TrueTrack transaction type
                $type = $this->mapTransactionType($transaction->amount);

                $transactions[] = [
                    'amount' => abs((float) $transaction->amount),
                    'date' => \Carbon\Carbon::parse($transaction->date),
                    'description' => trim($transaction->name ?? $transaction->memo ?? 'Unknown Transaction'),
                    'type' => $type,
                    'fitId' => $transaction->uniqueId ?? uniqid('ofx_', true),
                ];
            }
        }

        return $transactions;
    }

    /**
     * Map transaction amount to TrueTrack transaction type.
     * Positive amounts = credits (income), Negative amounts = debits (expenses).
     *
     * @param  float  $amount  Transaction amount from OFX
     * @return string 'credit' or 'debit'
     */
    private function mapTransactionType(float $amount): string
    {
        return $amount >= 0 ? 'credit' : 'debit';
    }

    /**
     * Create an OFX import record.
     *
     * @param  array  $data  Import data
     */
    public function createImport(array $data): OfxImport
    {
        return OfxImport::create([
            'filename' => $data['filename'],
            'file_hash' => $data['file_hash'],
            'account_id' => $data['account_id'],
            'file_path' => $data['file_path'],
            'user_id' => $data['user_id'],
            'status' => 'pending',
            'processed_count' => 0,
            'total_count' => 0,
        ]);
    }
}
