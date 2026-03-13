<?php

declare (strict_types = 1);

namespace viavario\compsyclient;

/**
 * Represents a single psychologist result from the Compsy search page.
 */
class CompsyResult
{
    /** @var string */
    public $name;

    /** @var string */
    public $detailUrl;

    /** @var string */
    public $status;

    /**
     * @param string $name      The psychologist's full name.
     * @param string $detailUrl The absolute URL to the psychologist's detail page.
     * @param string $status    The registration status as returned by the Compsy website.
     */
    public function __construct(string $name, string $detailUrl, string $status)
    {
        $this->name      = $name;
        $this->detailUrl = $detailUrl;
        $this->status    = $status;
    }

    /**
     * Returns true if the psychologist's status is "active" (case-insensitive).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return strtolower(trim($this->status)) === 'active';
    }

    /**
     * Returns the result as an associative array.
     *
     * @return array{name: string, detail_url: string, status: string, is_active: bool}
     */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'detail_url' => $this->detailUrl,
            'status'     => $this->status,
            'is_active'  => $this->isActive(),
        ];
    }
}
