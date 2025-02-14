<?php
/**
 * HeRe plugin for Craft CMS 3.x
 *
 * Use the SEO plugin redirects to write nginx and apache redirect map config files (perfect for headless Craft CMS Setups)
 *
 * @link      https://www.fork.de/
 * @copyright Copyright (c) 2020 Fork Unstable Media GmbH
 */

namespace fork\here;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\services\Plugins;

use craft\services\Sites;
use craft\web\Application;
use ether\seo\records\RedirectRecord;
use fork\here\services\EventHandler;
use fork\here\services\RedirectsMaps;
use fork\here\services\SiteHelper;
use yii\base\Application as YiiApplication;
use yii\base\Event;

/**
 * Class HeRe
 *
 * @author    Fork Unstable Media GmbH
 * @package   HeRe
 * @since     1.0.0
 *
 * @property EventHandler $eventHandler
 * @property RedirectsMaps $redirectsMaps
 * @property SiteHelper $siteHelper
 */
class HeRe extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var HeRe
     */
    public static HeRe $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSettings = false;

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Craft::setAlias('@fork/here', $this->getBasePath());

        //  this module depends on the ether/seo plugin, so check for it to be enabled
        if (Craft::$app->getPlugins()->isPluginEnabled('seo')) {
            //  register services
            $this->setComponents([
                'eventHandler' => EventHandler::class,
                'redirectsMaps' => RedirectsMaps::class,
                'siteHelper' => SiteHelper::class,
            ]);

            if (Craft::$app->getRequest()->getIsCpRequest()) {
                //  whenever a redirect (in the redirects config page of the SEO plugin) has been saved or deleted --> trigger re-/creation of the redirects maps
                Event::on(RedirectRecord::class, 'after*', [$this->eventHandler, 'handleRedirectRecordEvent']);
                //  whenever a site (in the multi-site settings) has been saved or deleted --> trigger re-/creation of the redirects maps
                Event::on(Sites::class, 'after*', [$this->eventHandler, 'handleSitesEvent']);
                //  at the end of processing any CP request --> re-/create the redirects maps
                Event::on(Application::class, YiiApplication::EVENT_AFTER_REQUEST, [$this->eventHandler, 'handleApplicationEvent']);
            }

            if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                $this->controllerNamespace = 'fork\here\console\controllers';
            }
        }

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // create empty map files to avoid (apache) errors
                    HeRe::getInstance()->redirectsMaps->recreateMaps();
                }
            }
        );
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_UNINSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // delete map files
                    HeRe::getInstance()->redirectsMaps->clear();
                }
            }
        );
    }
}
