<?php

namespace App\Common\Exceptions;

use Exception;

/**
 * Exception thrown when a stock operation would result in negative stock.
 * This acts as a safeguard against data integrity issues.
 */
class NegativeStockException extends Exception
{
    protected int $productId;
    protected int $currentQty;
    protected int $attemptedChange;

    public function __construct(
        int $productId,
        int $currentQty,
        int $attemptedChange,
        string $message = null
    ) {
        $this->productId = $productId;
        $this->currentQty = $currentQty;
        $this->attemptedChange = $attemptedChange;

        $resultingQty = $currentQty + $attemptedChange;
        $message = $message ?? "Stock operation would result in negative stock for product ID {$productId}. Current: {$currentQty}, Change: {$attemptedChange}, Resulting: {$resultingQty}";

        parent::__construct($message);
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getCurrentQty(): int
    {
        return $this->currentQty;
    }

    public function getAttemptedChange(): int
    {
        return $this->attemptedChange;
    }

    public function context(): array
    {
        return [
            'product_id' => $this->productId,
            'current_qty' => $this->currentQty,
            'attempted_change' => $this->attemptedChange,
            'resulting_qty' => $this->currentQty + $this->attemptedChange,
        ];
    }
}
