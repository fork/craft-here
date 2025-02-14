<?php

namespace fork\here\services;

use Craft;
use craft\models\Site;
use yii\base\Component;

/**
 * The SiteHelper service class provides some helper methods for site related tasks.
 *
 * @package fork\here\services
 */
class SiteHelper extends Component
{
    /**
     * list of sites indexed by their id
     *
     * @var Site[]
     */
    protected array $sites = [];

    /**
     * list of the sites' hosts indexed by their id
     *
     * @var string[]
     */
    protected array $siteHosts = [];

    /**
     * list of the sites' URI paths indexed by their id
     *
     * @var string[]
     */
    protected array $sitePaths = [];

    /**
     * Returns the hostname for given site ID.
     *
     * @param int $siteId
     *
     * @return string
     */
    public function getSiteHost(int $siteId): string
    {
        if (empty($this->siteHosts[$siteId])) {
            $this->siteHosts[$siteId] = $this->getUrlInfoForSite($siteId, PHP_URL_HOST);
        }

        return $this->siteHosts[$siteId];
    }

    /**
     * Returns the URI path for given site ID.
     *
     * @param int $siteId
     *
     * @return string
     */
    public function getSitePath(int $siteId): string
    {
        if (empty($this->sitePaths[$siteId])) {
            $path = trim($this->getUrlInfoForSite($siteId, PHP_URL_PATH), '/');
            $this->sitePaths[$siteId] = !empty($path) ? '/' . $path . '/' : $path;
        }

        return $this->sitePaths[$siteId];
    }

    /**
     * Returns a single part from the site's URL with given ID.
     *
     * @param int $siteId
     * @param int $urlPart one of the `PHP_URL_*` constants
     *
     * @return string
     *
     * @see PHP_URL_SCHEME
     * @see PHP_URL_HOST
     * @see PHP_URL_PORT
     * @see PHP_URL_USER
     * @see PHP_URL_PASS
     * @see PHP_URL_PATH
     * @see PHP_URL_QUERY
     * @see PHP_URL_FRAGMENT
     */
    public function getUrlInfoForSite(int $siteId, int $urlPart): string
    {
        $site = $this->getSite($siteId);

        if (!empty($site)) {
            return strval(parse_url($site->getBaseUrl(), $urlPart));
        }

        return '';
    }

    /**
     * Returns the site for given id.
     *
     * @param int $id
     *
     * @return Site|null
     */
    public function getSite(int $id): ?Site
    {
        if (empty($this->sites[$id])) {
            $this->sites[$id] = Craft::$app->getSites()->getSiteById($id);
        }

        return $this->sites[$id];
    }
}
