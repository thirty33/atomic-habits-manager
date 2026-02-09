<?php

namespace App\Services\Frontend;

use App\Services\Frontend\UIElements\ResourceDetailLine;
use Exception;

final class ResourceDetailGenerator
{
    private array $lines = [];

    private array $sections = [];

    /**
     * @throws Exception
     */
    public function addLine(ResourceDetailLine $line): self
    {
        $this->lines[] = $line->generate();

        return $this;
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @param  ResourceDetailLine[]  $lines
     *
     * @throws Exception
     */
    public function addSection(string $title, array $lines, ?string $dataKey = null): self
    {
        $this->sections[] = [
            'title' => __($title),
            'data_key' => $dataKey,
            'lines' => array_map(fn (ResourceDetailLine $line) => $line->generate(), $lines),
        ];

        return $this;
    }

    public function getSections(): array
    {
        return $this->sections;
    }
}
