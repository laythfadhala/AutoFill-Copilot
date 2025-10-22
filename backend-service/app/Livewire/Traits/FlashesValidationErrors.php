<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait FlashesValidationErrors
{
    /**
     * Validate data and flash errors if validation fails
     * Throws ValidationException to stop execution
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateAndFlash(array $data)
    {
        $validator = Validator::make($data, $this->rules);

        if ($validator->fails()) {
            $this->flashValidationErrors($validator->errors());
            throw new ValidationException($validator);
        }
    }

    /**
     * Flash validation errors as a session message
     *
     * @param \Illuminate\Support\MessageBag $errors
     */
    private function flashValidationErrors($errors)
    {
        $errorMessages = [];
        foreach ($errors->all() as $error) {
            $errorMessages[] = $error;
        }
        session()->flash('error', implode(' ', $errorMessages));
    }
}
