<?php

namespace IPS\toolbox\modules\admin\devcenter;

use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Request;
use IPS\toolbox\DevCenter\Sources;

use function defined;
use function header;


/* To prevent PHP errors (extending class does not exist) revealing path */

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * sources
 */
class _sources extends Controller
{
    use \IPS\toolbox\Shared\Sources;

    /**
     * @brief    Has been CSRF-protected
     */
    public static $csrfProtected = true;
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Sources
     */
    protected $elements;
    protected $front = false;

    public function execute()
    {
        Dispatcher::i()->checkAcpPermission('sources_manage');
        Sources::menu();
        $app = (string)Request::i()->appKey;
        if (!$app) {
            $app = 'core';
        }
        $this->application = Application::load($app);
        $this->elements = new Sources($this->application);
        parent::execute();
    }



}
