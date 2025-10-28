<?php

namespace Terminal42\NodeBundle;

class NodeElement
{
    public function __construct(
        private readonly array $row,
        private readonly string $renderedHtml,
    )
    {
    }

    public function getRow(): array
    {
        return $this->row;
    }

    public function getRenderedHtml(): string
    {
        return $this->renderedHtml;
    }
}
