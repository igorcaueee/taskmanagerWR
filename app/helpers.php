<?php

if (! function_exists('formatFileSize')) {
    function formatFileSize(?int $bytes): string
    {
        if ($bytes === null || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 1).' '.$units[$i];
    }
}

if (! function_exists('appBuildVersion')) {
    function appBuildVersion(): string
    {
        $manifest = public_path('build/manifest.json');

        return (string) (file_exists($manifest) ? filemtime($manifest) : filemtime(base_path('composer.lock')));
    }
}
