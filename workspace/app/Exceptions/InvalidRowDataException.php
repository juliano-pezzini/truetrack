<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidRowDataException extends Exception
{
    protected int $rowNumber;

    protected string $field;

    protected mixed $rawValue;

    public function __construct(string $message, int $rowNumber = 0, string $field = '', mixed $rawValue = null)
    {
        parent::__construct($message);
        $this->rowNumber = $rowNumber;
        $this->field = $field;
        $this->rawValue = $rawValue;
    }

    public function getRowNumber(): int
    {
        return $this->rowNumber;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getRawValue(): mixed
    {
        return $this->rawValue;
    }
}
