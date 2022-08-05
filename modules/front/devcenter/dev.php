<?php

namespace IPS\toolbox\modules\front\devcenter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Request;
use IPS\toolbox\DevCenter\Dev;
use IPS\toolbox\Shared\Assets;

if (!\defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * dev
 */
class _dev extends \IPS\Dispatcher\Controller
{
    use Assets;

    /**
     * @brief    Has been CSRF-protected
     */
    public static $csrfProtected = true;
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Elements
     */
    protected $elements;

    public function execute()
    {
        $this->application = Application::load(Request::i()->appKey);
        $this->elements = new Dev($this->application);
        parent::execute();
    }
}
