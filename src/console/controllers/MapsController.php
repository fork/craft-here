<?php

/** @noinspection PhpUnused */

namespace fork\here\console\controllers;

use craft\console\Controller;
use fork\here\HeRe;
use Throwable;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

/**
 * Manages redirects maps.
 *
 * @package fork\here\console\controllers
 */
class MapsController extends Controller
{
    /**
     * Re-/creates the redirects maps while depending on the redirects as set in the SEO plugin.
     *
     * @return int
     *
     * @throws Throwable
     */
    public function actionRecreate(): int
    {
        HeRe::getInstance()->redirectsMaps->recreateMaps();
        $this->stdout('✅ Re-/created redirects maps.' . PHP_EOL, BaseConsole::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Deletes all existing redirects maps.
     *
     * @return int
     *
     * @throws Throwable
     */
    public function actionClear(): int
    {
        HeRe::getInstance()->redirectsMaps->clear();
        $this->stdout('✅ Removed redirects maps.' . PHP_EOL, BaseConsole::FG_GREEN);

        return ExitCode::OK;
    }
}
