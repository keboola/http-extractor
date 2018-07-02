<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getBaseUrl(): string
    {
        return $this->getValue(['parameters', 'baseUrl']);
    }

    /**
     * @return mixed[]
     */
    public function getClientOptions(): array
    {
        $options = [];
        if ($this->getValue(['parameters', 'maxRedirects'], false)) {
            $options['maxRedirects'] = $this->getValue(['parameters', 'maxRedirects']);
        }
        return $options;
    }

    public function getPath(): string
    {
        return $this->getValue(['parameters', 'path']);
    }
}
