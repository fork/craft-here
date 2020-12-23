<?php

namespace fork\here\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use fork\here\HeRe;
use Throwable;
use yii\console\ExitCode;

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
     * @return string
     *
     * @throws \Throwable
     */
    public function actionRecreate()
    {
        try {
            HeRe::getInstance()->redirectsMaps->recreateMaps();
            $this->stdout('✅ Re-/created redirects maps.' . PHP_EOL, Console::FG_GREEN);
        } catch (Throwable $e) {
            throw $e;
        }

        return ExitCode::OK;
    }

    /**
     * Deletes all existing redirects maps.
     *
     * @return int
     *
     * @throws \Throwable
     */
    public function actionClear()
    {
        try {
            HeRe::getInstance()->redirectsMaps->clear();
            $this->stdout('✅ Removed redirects maps.' . PHP_EOL, Console::FG_GREEN);
        } catch (Throwable $e) {
            throw $e;
        }

        return ExitCode::OK;
    }
}
