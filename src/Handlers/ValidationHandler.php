<?php

namespace SMartins\Exceptions\Handlers;

use SMartins\Exceptions\JsonApi\Error;
use SMartins\Exceptions\JsonApi\Source;
use SMartins\Exceptions\JsonApi\ErrorCollection;

class ValidationHandler extends AbstractHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $errors = (new ErrorCollection)->setStatusCode(400);

        $failedFieldsRules = $this->getFailedFieldsRules();

        foreach ($this->getFailedFieldsMessages() as $field => $messages) {
            foreach ($messages as $key => $message) {
                $code = $this->getValidationCode($failedFieldsRules, $key, $field);
                $title = $this->getValidationTitle($failedFieldsRules, $key, $field);

                $error = (new Error)->setStatus(422)
                    ->setSource((new Source())->setPointer($field))
                    ->setTitle($title ?? $this->getDefaultTitle())
                    ->setDetail($message);

                if (! is_null($code)) {
                    $error->setCode($code);
                }

                $errors->push($error);
            }
        }

        return $errors;
    }

    /**
     * Get the title of response based on rules and field getting from translations.
     *
     * @param  array  $failedFieldsRules
     * @param  string $key
     * @param  string $field
     * @return string|null
     */
    public function getValidationTitle(array $failedFieldsRules, string $key, string $field)
    {
        $title = __('exception::exceptions.validation.title', [
            'fails' => array_keys($failedFieldsRules[$field])[$key],
            'field' => $field,
        ]);

        return is_array($title) ? $title[0] : $title;
    }

    /**
     * Get the code of validation error from config.
     *
     * @param  array  $failedFieldsRules
     * @param  string $key
     * @param  string $field
     * @return string|null
     */
    public function getValidationCode(array $failedFieldsRules, string $key, string $field)
    {
        $rule = strtolower(array_keys($failedFieldsRules[$field])[$key]);

        return config('json-exception-handler.codes.validation_fields.'.$field.'.'.$rule);
    }

    /**
     * Get message based on exception type. If exception is generated by
     * $this->validate() from default Controller methods the exception has the
     * response object. If exception is generated by Validator::make() the
     * messages are get different.
     *
     * @return array
     */
    public function getFailedFieldsMessages(): array
    {
        return $this->exception->validator->messages()->messages();
    }

    /**
     * Get the rules failed on fields.
     *
     * @return array
     */
    public function getFailedFieldsRules(): array
    {
        return $this->exception->validator->failed();
    }
}
