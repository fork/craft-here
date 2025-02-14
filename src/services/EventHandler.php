<?php

namespace fork\here\services;

use craft\services\Sites;
use craft\web\Application;
use ether\seo\records\RedirectRecord;
use yii\base\Component;
use yii\base\Event;

/**
 * The EventHandler class provides methods for handling several events.
 *
 * @package fork\here\services
 */
class EventHandler extends Component
{
    /**
     * @var \fork\here\services\RedirectsMaps
     */
    protected $redirectsMaps;

    /**
     * EventHandler constructor.
     *
     * @param \fork\here\services\RedirectsMaps $redirectsMaps
     * @param array $config
     */
    public function __construct(RedirectsMaps $redirectsMaps, $config = [])
    {
        $this->redirectsMaps = $redirectsMaps;

        parent::__construct($config);
    }

    /**
     * Handles the given event (whose origin is a redirect record after being saved or deleted) for triggering the re-/creation of the redirects maps.
     *
     * @param \yii\base\Event $event
     */
    public function handleRedirectRecordEvent(Event $event)
    {
        if ($event->sender instanceof RedirectRecord and in_array($event->name, [
            RedirectRecord::EVENT_AFTER_INSERT,
            RedirectRecord::EVENT_AFTER_UPDATE,
            RedirectRecord::EVENT_AFTER_DELETE,
        ])) {
            $this->redirectsMaps->triggerRecreation();
        }
    }

    /**
     * Handles the given event (whose origin is a service for handling sites like saving and deleting site records) for triggering the re-/creation of the
     * redirects maps.
     *
     * @param \yii\base\Event $event
     */
    public function handleSitesEvent(Event $event)
    {
        if ($event->sender instanceof Sites and in_array($event->name, [
            Sites::EVENT_AFTER_SAVE_SITE,
            Sites::EVENT_AFTER_DELETE_SITE,
        ])) {
            $this->redirectsMaps->triggerRecreation();
        }
    }

    /**
     * Handles the given event (whose origin is the application at the end of processing the request) for re-/creating the redirects maps.
     *
     * @param \yii\base\Event $event
     *
     * @throws \yii\base\Exception
     */
    public function handleApplicationEvent(Event $event)
    {
        if ($event->sender instanceof Application and $event->name == Application::EVENT_AFTER_REQUEST) {
            $this->redirectsMaps->recreateMapsIfTriggered();
        }
    }
}
