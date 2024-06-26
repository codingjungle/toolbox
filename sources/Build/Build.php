<?php

/**
 * @brief       Build Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.2.0
 * @version     -storm_version-
 */

namespace IPS\toolbox;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Application\BuilderIterator;
use IPS\Data\Store;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\Singleton;
use IPS\Request;
use IPS\toolbox\Profiler\Debug;
use Phar;
use PharData;
use RuntimeException;

use function chmod;
use function defined;
use function explode;
use function is_dir;
use function mkdir;
use function preg_match;
use function preg_replace_callback;
use function sprintf;
use function implode;
use const IPS\IPS_FOLDER_PERMISSION;


\IPS\toolbox\Application::loadAutoLoader();

class _Build extends Singleton
{

    protected static $instance;

    /**
     * Undocumented function
     *
     * @return void
     */
    public function export()
    {
        implode(',',$foo);
        if (!Application::appIsEnabled('toolbox') || !\IPS\IN_DEV) {
            throw new InvalidArgumentException('toolbox not installed');
        }

        $app = Request::i()->appKey;
        $application = Application::load($app);
        $title = $application->_title;
        Member::loggedIn()->language()->parseOutputForDisplay($title);
        $newLong = $application->long_version;

        if (empty($application->version) !== true) {
            $newShort = $application->version;
        } else {
            $newShort = '1.0.0';
            $newLong = 10000;
        }
        $reg = '#\sBeta\s(\d)#';
        preg_match($reg, $newShort, $match);
        $beta = 1;
        if (isset($match[1])) {
            $beta = $match[1] + 1;
        }
        $form = Form::create();
        $form->addDummy('Previous Long Version', $newLong);
        $form->addDummy('Previous Short Version', $newShort);
        $form->addElement('toolbox_increment', 'yn')->value(1)->toggles(
            ['toolbox_long_version', 'toolbox_short_version'],
            true
        );
        $form->addElement('toolbox_long_version', 'number')->label('Long Version')->required()->empty($newLong);
        $form->addElement('toolbox_short_version')->label('Short Version')->required()->empty($newShort);
        $form->addElement('toolbox_beta', 'yn');
        $form->addElement('toolbox_beta_version', 'number')->required()->empty($beta);

        if(defined('DT_SLASHER') && DT_SLASHER === true) {
            $form->addElement('toolbox_skip_dir', 'stack')->label('Skip Directories')->description(
                'Folders to skip using slasher on.'
            )->empty(
                [
                    '3rdparty',
                    'vendor',
                ]
            );
            $form->addElement('toolbox_skip_files', 'stack')->label('Skip Files')->description(
                'Files to skip using slasher on.'
            );
        }
        if ($values = $form->values()) {
            $long = $values['toolbox_long_version'];
            $short = $values['toolbox_short_version'];
            $short = preg_replace_callback($reg, function ($m) {
                return '';
            }, $short);
            if (!$values['toolbox_beta'] && isset($values['toolbox_increment']) && $values['toolbox_increment']) {
                $exploded = explode('.', $short);
                $end = $exploded[2] ?? 0;
                $short = "{$exploded[0]}.{$exploded[1]}." . ((int)$end + 1);
                $long++;
            }

            if (isset($values['toolbox_beta']) && $values['toolbox_beta']) {
                $short .= ' Beta ' . $values['toolbox_beta_version'];
            }

            $application->long_version = $long;
            $application->version = $short;
            $application->save();
            unset(Store::i()->applications);
            $path = \IPS\Application::getRootPath() . '/exports/' . $application->directory . '/' . $long . '/';

            try {
                if(defined('DT_SLASHER') && DT_SLASHER === true) {
                    Slasher::i()->start(
                        $application,
                        $values['toolbox_skip_files'] ?? [],
                        $values['toolbox_skip_dir'] ?? []
                    );
                }
                try {
                    $application->assignNewVersion($long, $short);
                    $application->build();
                    $application->save();
                    if (!is_dir($path)) {
                        if (!mkdir($path, IPS_FOLDER_PERMISSION, true) && !is_dir($path)) {
                            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
                        }
                        chmod($path, IPS_FOLDER_PERMISSION);
                    }
                    $pharPath = $path . $application->directory . ' - ' . $application->version . '.tar';
                    $download = new PharData($pharPath, 0, $application->directory . '.tar', Phar::TAR);
                    $download->buildFromIterator(new BuilderIterator($application));
                } catch (Exception $e) {
                    Debug::log($e, 'phar');
                }
            } catch (Exception $e) {
                Debug::log($e, 'phar');
            }

            unset(Store::i()->applications, $download);
            $url = Url::internal('app=core&module=applications&controller=applications');
            Output::i()->redirect($url, $application->_title . ' successfully built!');
        }

        Output::i()->title = 'Build ' . $application->_title;
        Output::i()->output = $form;
    }


}
