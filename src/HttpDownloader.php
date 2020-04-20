<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer;

use Composer\Config as ComposerConfig;
use Composer\IO\IOInterface;
use Composer\Util\HttpDownloader as ComposerHttpDownloader;

class HttpDownloader extends ComposerHttpDownloader
{
    protected function patchURL(string $url): string
    {
        $patchedUrl = $this->urlMapper->applyMappings($url);

        if ($patchedUrl !== $url) {
            $this->io->write(\sprintf('%s(url=%s): %s', __METHOD__, $url, $patchedUrl), true, IOInterface::DEBUG);
        }

        return $patchedUrl;
    }

    /**
     * @inheritdoc
     */
    //public function get($originUrl, $fileUrl, $additionalOptions = [], $fileName = null, $progress = true)
    public function get($url, $options = array())
    {
        $patchedUrl = $this->patchURL($url);
        return parent::get($patchedUrl, $options);
    }
}
