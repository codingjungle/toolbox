<?php


namespace IPS\toolbox\modules\admin\devcenter;

use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Request;
use IPS\toolbox\DevCenter\Dev;
use IPS\toolbox\Shared\Assets;

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
class _dev extends Controller
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
        Dispatcher::i()->checkAcpPermission('sources_manage');
        $this->application = Application::load(Request::i()->appKey);
        $this->elements = new Dev($this->application);
        parent::execute();
    }
}
