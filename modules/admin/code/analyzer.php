<?php

/**
 * @brief       View Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Code Analyzer
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\modules\admin\code;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Dispatcher\Admin;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Code\Db;
use IPS\toolbox\Code\ErrorCodes;
use IPS\toolbox\Code\FileStorage;
use IPS\toolbox\Code\InterfaceFolder;
use IPS\toolbox\Code\Langs;
use IPS\toolbox\Code\RootPath;
use IPS\toolbox\Code\Settings;
use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;

use function array_merge;
use function defined;
use function header;
use function in_array;
use function is_array;
use function ksort;
use function round;

/* To prevent PHP errors (extending class does not exist) revealing path */

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * view
 */
class _analyzer extends Controller
{
    use \IPS\toolbox\Shared\Analyzer;
    /**
     * @brief    Has been CSRF-protected
     */
    public static $csrfProtected = true;

    /**
     * @inheritdoc
     * @throws RuntimeException
     */
    public function execute()
    {
        Output::i()->cssFiles = array_merge(Output::i()->cssFiles, Theme::i()->css('dtcode.css', 'toolbox', 'admin'));
        Output::i()->jsFiles = array_merge(
            Output::i()->jsFiles,
            Output::i()->js('admin_toggles.js', 'toolbox', 'admin')
        );

        Admin::i()->checkAcpPermission('view_manage');

        parent::execute();
    }
}
