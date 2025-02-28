<?php

namespace fork\here\services;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use ether\seo\records\RedirectRecord;
use ether\seo\Seo;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * The RedirectsMaps service class provides methods for re-/creating redirects maps.
 *
 * @package fork\here\services
 *
 * @see https://www.tendenci.com/help-files/nginx-redirect-maps/
 */
class RedirectsMaps extends Component
{
    /**
     * status code respectively the redirect type for "301 MOVED PERMANENTLY"
     */
    public const STATUS_CODE_301_MOVED_PERMANENTLY = '301';
    /**
     * status code respectively the redirect type for "302 FOUND" (aka "302 MOVED TEMPORARILY")
     */
    public const STATUS_CODE_302_FOUND = '302';
    /**
     * list of redirect types indexed by response status code
     */
    public const REDIRECT_TYPES = [
        self::STATUS_CODE_301_MOVED_PERMANENTLY => 'moved',
        self::STATUS_CODE_302_FOUND => 'found',
    ];

    /**
     * the server type ("nginx" or "apache")
     *
     * @var string $serverType
     */
    protected string $serverType;

    /**
     * the config from site/config/redirects.php
     *
     * @var array $config
     */
    protected array $config;

    /**
     * flag to determine if the redirects map has to be re-/created
     *
     * @var bool
     */
    protected bool $isRecreationTriggered = false;

    /**
     * @var SiteHelper $siteHelper
     */
    protected SiteHelper $siteHelper;

    /**
     * RedirectsMaps constructor.
     *
     * @param SiteHelper $siteHelper
     * @param array $config
     */
    public function __construct(SiteHelper $siteHelper, array $config = [])
    {
        $this->siteHelper = $siteHelper;
        $this->config = Craft::$app->getConfig()->getConfigFromFile('redirects');
        $this->serverType = $this->config['serverType'] ?? 'nginx';

        parent::__construct($config);
    }

    /**
     * Sets the trigger for re-/creating the redirects maps.
     *
     * Must be invoked before re-/creating the redirects maps using `recreateMapIfTriggered()` method.
     *
     * @param bool $recreateMap
     *
     * @see RedirectsMaps::recreateMapsIfTriggered
     */
    public function triggerRecreation(bool $recreateMap = true): void
    {
        $this->isRecreationTriggered = $recreateMap;
    }

    /**
     * Re-/creates the redirects maps unless the re-/creation is not properly triggered using the `triggerRecreation()` method.
     *
     * @throws Exception
     *
     * @see RedirectsMaps::triggerRecreation
     */
    public function recreateMapsIfTriggered(): void
    {
        if ($this->isRecreationTriggered) {
            $this->recreateMaps();
        }
    }

    /**
     * Re-/creates the redirects maps regardless of re-/creation has been triggered using the `triggerRecreation()` method.
     *
     * @throws Exception
     */
    public function recreateMaps(): void
    {
        $maps = $this->getRedirectsMapsByType();

        $this->recreateMapFile($maps, static::STATUS_CODE_301_MOVED_PERMANENTLY);
        $this->recreateMapFile($maps, static::STATUS_CODE_302_FOUND);

        // apache doesn't need reload
        if ($this->serverType === 'nginx') {
            $this->reloadNginxConfigs();
        }
    }

    /**
     * Returns an array of redirects maps grouped by the HTTP response code respectively by the redirect type which is either "301" or "302".
     *
     * @return array
     * @throws Exception
     */
    protected function getRedirectsMapsByType(): array
    {
        $maps = [];
        $siteIds = Craft::$app->getSites()->getAllSiteIds();

        $redirectRecords = Seo::getInstance()->redirects->findAllRedirects();
        $hosts = [];

        // init empty redirects maps (to avoid server errors for missing map files)
        foreach ($siteIds as $siteId) {
            $host = $this->siteHelper->getSiteHost($siteId);
            $hosts[$siteId] = $host;
            $maps[$host]['301'] = [];
            $maps[$host]['302'] = [];
        }

        /** @var RedirectRecord[]|null[] $redirects */
        foreach ($redirectRecords as $redirects) {
            foreach ($redirects as $redirect) {
                if (!empty($redirect)) {
                    $siteId = $redirect->siteId;

                    //  in case of this redirect is set for all sites
                    if (empty($siteId)) {
                        foreach ($siteIds as $siteId) {
                            $host = $hosts[$siteId];
                            $maps[$host][$redirect->type][] = $this->getEntryForRedirectMap($redirect, $siteId);
                        }
                        continue;
                    }

                    //  this redirect is set for just one specific site
                    $host = $hosts[$siteId];
                    $maps[$host][$redirect->type][] = $this->getEntryForRedirectMap($redirect, $siteId);
                }
            }
        }

        return $maps;
    }

    /**
     * Returns an entry to be appended to the redirects map for given redirect record.
     *
     * @param RedirectRecord $redirect
     * @param int $siteId
     *
     * @return string
     * @throws Exception
     */
    protected function getEntryForRedirectMap(RedirectRecord $redirect, int $siteId): string
    {
        $basePath = $this->siteHelper->getSitePath($siteId);

        $trailingSlash = mb_substr($redirect->uri, -1) === '/';
        $redirectFrom = FileHelper::normalizePath($basePath . $redirect->uri);
        $redirectTo = $this->isPath($redirect->to) ? FileHelper::normalizePath($basePath . $redirect->to) : $redirect->to;

        $redirectFrom = StringHelper::ensureLeft($redirectFrom, '/');
        if ($trailingSlash) {
            $redirectFrom = StringHelper::ensureRight($redirectFrom, '/');
        }
        $redirectTo = UrlHelper::isAbsoluteUrl($redirectTo) ? $redirectTo : StringHelper::ensureLeft($redirectTo, '/');

        if ($this->serverType === 'nginx') {
            return '    "' . $redirectFrom . '" "' . $redirectTo . '";';
        } elseif ($this->serverType === 'apache') {
            return $redirectFrom . ' ' . $redirectTo;
        } else {
            throw new Exception("$this->serverType not supported!");
        }
    }

    /**
     * Checks if given string is a path or something different (e.g. a URL).
     *
     * @param string $string
     *
     * @return bool
     */
    protected function isPath(string $string): bool
    {
        return !boolval(filter_var($string, FILTER_VALIDATE_URL));
    }

    /**
     * Re/creates the redirects map for given list of redirects and status code.
     *
     * @param array $maps
     * @param string $statusCode
     *
     * @throws Exception
     */
    protected function recreateMapFile(array $maps, string $statusCode): void
    {
        foreach ($maps as $siteHost => $mapsForSite) {
            $tempDir = FileHelper::normalizePath(Craft::$app->getPath()->getTempPath());
            $destDir = FileHelper::normalizePath($this->getRedirectsDir() . $siteHost);
            $filename = '/redirects-' . $statusCode . '.map';
            $tempFile = $tempDir . $filename;
            $destFile = $destDir . $filename;

            if ($this->serverType === 'nginx') {
                $content = $this->getRedirectsMapNginxContent($mapsForSite, $statusCode);
            } elseif ($this->serverType === 'apache') {
                $content = $this->getRedirectsMapApacheContent($mapsForSite, $statusCode);
            } else {
                throw new Exception("$this->serverType not supported!");
            }

            if (!file_exists($tempDir)) {
                FileHelper::createDirectory($tempDir);
            }

            if ($content ? file_put_contents($tempFile, $content) : touch($tempFile)) {
                if (!file_exists($destDir)) {
                    FileHelper::createDirectory($destDir);
                }
                rename($tempFile, $destFile);
            }
        }
    }

    /**
     * Returns the file content for a redirects map for given redirects maps and status code (which is called "redirect type" in used SEO plugin).
     *
     * @param array $maps
     * @param string $statusCode
     *
     * @return string
     */
    protected function getRedirectsMapNginxContent(array $maps, string $statusCode): string
    {
        $content = '';

        if (!empty($maps[$statusCode])) {
            $content .= 'map $request_uri $redirect_' . self::REDIRECT_TYPES[$statusCode] . ' {' . PHP_EOL;
            $content .= implode(PHP_EOL, $maps[$statusCode]) . PHP_EOL;
            $content .= '}' . PHP_EOL;
        }

        return $content;
    }

    /**
     * Returns the file content for a redirects map for given redirects maps and status code (which is called "redirect type" in used SEO plugin).
     *
     * @param array $maps
     * @param string $statusCode
     *
     * @return string
     */
    protected function getRedirectsMapApacheContent(array $maps, string $statusCode): string
    {
        $content = '';

        if (!empty($maps[$statusCode])) {
            $content .= implode(PHP_EOL, $maps[$statusCode]) . PHP_EOL;
        }

        return $content;
    }

    /**
     * Tells the nginx server to reload its configs for the current redirects maps to take effect.
     */
    protected function reloadNginxConfigs(): void
    {
        $reloadCommand = $this->config && !empty($this->config['redirectsReloadCommand']) ? $this->config['redirectsReloadCommand'] : null;

        if ($reloadCommand) {
            exec($reloadCommand, $output, $returned);
        }

        //  TODO: any error-handling (like checking the output and/or the returned value)?
    }

    /**
     * Clears the redirects directory.
     *
     * @throws ErrorException
     */
    public function clear(): void
    {
        FileHelper::clearDirectory(FileHelper::normalizePath($this->getRedirectsDir()), [
            'except' => ['.gitignore'],
        ]);
    }

    /**
     * Returns the directory path where all redirects maps are stored.
     *
     * The returned path is not normalized, do so using the `\craft\helpers\FileHelper` service.
     *
     * @return string
     *
     * @see FileHelper
     */
    protected function getRedirectsDir(): string
    {
        return Craft::getAlias('@webroot') . '/../redirects/';
    }
}
