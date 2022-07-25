<?php

/**
 * @brief       Build Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.8
 * @version     -storm_version-
 */


namespace IPS\toolbox\modules\front\bt;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Build\Versions;
use IPS\toolbox\Shared\Analyzer;

use function array_merge;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * build
 */
class _build extends \IPS\Dispatcher\Controller
{
    use Analyzer;
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
        Output::i()->cssFiles = array_merge(Output::i()->cssFiles, Theme::i()->css('dtcode.css', 'toolbox', 'admin'));
        Output::i()->jsFiles = array_merge(
            Output::i()->jsFiles,
            Output::i()->js('admin_toggles.js', 'toolbox', 'admin')
        );
		parent::execute();
	}
    protected function download(){
        $app = Request::i()->myApp;
        $data = Store::i()->dtversions;
        if(isset($data[$app])){
            $values = $data[$app];
            $versions = (new Versions($app,$values));
            $error = $versions->build();
            sleep(2);
//            unset($data[$app]);
            Store::i()->dtversions = $data;
            \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('bt','toolbox','front')->downloadInfo($versions->path, $versions->error, $versions->app->_title);
        }
    }
}