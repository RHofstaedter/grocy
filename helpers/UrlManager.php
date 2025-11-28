<?php

namespace Grocy\Helpers;

class UrlManager
{
    public function __construct(string $basePath)
    {
        $this->BasePath = $basePath === '/' ? $this->GetBaseUrl() : $basePath;
    }

    protected string $BasePath;

    public function ConstructUrl($relativePath, $isResource = false): string
    {
        if (GROCY_DISABLE_URL_REWRITING === false || $isResource === true) {
            return rtrim((string) $this->BasePath, '/') . $relativePath;
        } else { // Is not a resource and URL rewriting is disabled
            return rtrim((string) $this->BasePath, '/') . '/index.php' . $relativePath;
        }
    }

    private function GetBaseUrl(): string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && str_contains((string) $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https')) {
            $_SERVER['HTTPS'] = 'on';
        }

        return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . ('://' . $_SERVER[HTTP_HOST]);
    }
}
