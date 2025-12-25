<?php

namespace App\Common\Exceptions;

use Exception;

/**
 * Exception thrown when there is insufficient stock for an operation.
 * Used primarily during sales checkout and stock decrement operations.
 */
class StockInsufficiencyException extends Exception
{
    protected int $productId;
    protected int $requestedQty;
    protected int $availableQty;

    public function __construct(
        int $productId,
        int $requestedQty,
        int $availableQty,
        string $message = null
    ) {
        $this->productId = $productId;
        $this->requestedQty = $requestedQty;
        $this->availableQty = $availableQty;

        $message = $message ?? "Insufficient stock for product ID {$productId}. Requested: {$requestedQty}, Available: {$availableQty}";

        parent::__construct($message);
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getRequestedQty(): int
    {
        return $this->requestedQty;
    }

    public function getAvailableQty(): int
    {
        return $this->availableQty;
    }

    public function context(): array
    {
        return [
            'product_id' => $this->productId,
            'requested_qty' => $this->requestedQty,
            'available_qty' => $this->availableQty,
        ];
    }
}
