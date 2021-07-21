<?php
/**
 * Filter exception class.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2021
 * @license MIT
 */

namespace Laramore\Exceptions;

class FilterException extends LaramoreException
{
    protected $errors;

    public function __construct(array $errors, string $message='An issue were detected with filters')
    {
        parent::__construct($message);

        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
