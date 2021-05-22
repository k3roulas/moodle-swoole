<?php

class ExceptionHeader extends Exception {

    /** @var string */
    private $key;

    /** @var string */
    private $value;

    /** @var string */
    private $status;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        var_dump($message, $code , $previous = null);
//        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param string $header
     * @return ExecptionHeader
     */
    public function setHeader(string $key, string $value, int $status): ExceptionHeader
    {
        $this->key = $key;
        $this->value = $value;
        $this->status = $status;

        return $this;
    }
}
