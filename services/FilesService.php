<?php

namespace Grocy\Services;

use Gumlet\ImageResize;
use Gumlet\ImageResizeException;

class FilesService extends BaseService
{
    public const FILE_SERVE_TYPE_PICTURE = 'picture';

    private $StoragePath;

    public function __construct()
    {
        $this->StoragePath = GROCY_DATAPATH . '/storage';
        if (!file_exists($this->StoragePath)) {
            mkdir($this->StoragePath);
        }

        if (GROCY_MODE === 'demo' || GROCY_MODE === 'prerelease') {
            $dbSuffix = GROCY_DEFAULT_LOCALE;
            if (defined('GROCY_DEMO_DB_SUFFIX')) {
                $dbSuffix = GROCY_DEMO_DB_SUFFIX;
            }

            $this->StoragePath = $this->StoragePath . '/' . $dbSuffix;
            if (!file_exists($this->StoragePath)) {
                mkdir($this->StoragePath);
            }
        }
    }

    public function downscaleImage($group, $fileName, $bestFitHeight = null, $bestFitWidth = null)
    {
        $filePath = $this->getFilePath($group, $fileName);
        $fileNameWithoutExtension = pathinfo($filePath, PATHINFO_FILENAME);
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        $fileNameDownscaled = $fileNameWithoutExtension . '__downscaledto' . ($bestFitHeight ? $bestFitHeight : 'auto')
            . 'x' . ($bestFitWidth ? $bestFitWidth : 'auto') . '.' . $fileExtension;
        $filePathDownscaled = $this->getFilePath($group, $fileNameDownscaled);

        try {
            if (!file_exists($filePathDownscaled)) {
                $image = new ImageResize($filePath);

                if ($bestFitHeight !== null && $bestFitHeight !== null) {
                    $image->resizeToBestFit($bestFitWidth, $bestFitHeight);
                } elseif ($bestFitHeight !== null) {
                    $image->resizeToHeight($bestFitHeight);
                } elseif ($bestFitWidth !== null) {
                    $image->resizeToWidth($bestFitWidth);
                }

                $image->save($filePathDownscaled);
            }
        } catch (ImageResizeException) {
            return $filePath;
        }

        return $filePathDownscaled;
    }

    public function deleteFile($group, $fileName)
    {
        $filePath = $this->getFilePath($group, $fileName);

        if (file_exists($filePath)) {
            $fileNameWithoutExtension = pathinfo($filePath, PATHINFO_FILENAME);

            // Then the file is an image
            if (getimagesize($filePath) !== false) {
                // Also delete all corresponding "__downscaledto" files when deleting an image
                $groupFolderPath = $this->StoragePath . '/' . $group;
                $files = scandir($groupFolderPath);
                foreach ($files as $file) {
                    if (string_starts_with($file, $fileNameWithoutExtension . '__downscaledto')) {
                        unlink($this->getFilePath($group, $file));
                    }
                }
            }

            unlink($filePath);
        }
    }

    public function getFilePath($group, $fileName)
    {
        $groupFolderPath = $this->StoragePath . '/' . $group;

        if (!file_exists($groupFolderPath)) {
            mkdir($groupFolderPath);
        }

        return $groupFolderPath . '/' . $fileName;
    }
}
