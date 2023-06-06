<?php

/**
 * @brief       Proxy Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       4.0.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\modules\admin\proxy;

use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\toolbox\Proxy\Proxyclass;

use function count;
use function defined;
use function header;
use function in_array;

use const IPS\NO_WRITES;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * proxy
 * @deprecated
 */
class _proxy extends Controller
{
    /**
     * @brief    Has been CSRF-protected
     */
    public static $csrfProtected = true;

    /**
     * Execute
     *
     * @return    void
     */
    public function execute()
    {
        if (NO_WRITES === true) {
            Output::i()->error(
                'Proxy generator can not be used atm, NO_WRITES is enabled in the constants.php.',
                '100foo'
            );
        }
        Dispatcher::i()->checkAcpPermission('proxy_manage');

        parent::execute();
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        Output::i()->title = Member::loggedIn()->language()->addToStack('dtproxy_proxyclass_title');
        Output::i()->output = new MultipleRedirect(
            $this->url->csrf(), static function ($data) {
            if (!$data || !count($data)) {
                $data = [];
                $data['total'] = Proxyclass::i()->dirIterator();
                $data['current'] = 0;
                $data['progress'] = 0;
                $data['firstRun'] = 1;
                Proxyclass::i()->buildHooks();
            }

            $run = Proxyclass::i()->run($data);

            if ($run === null) {
                return null;
            } else {
                /**
                 * hacky af, but what is a boy to do? :P
                 */
                if (in_array('current', $run)) {
                    $progress = isset($run['progress']) ? $run['progress'] : 0;

                    if ($run['total'] && $run['current']) {
                        $progress = ($run['current'] / $run['total']) * 100;
                    }

                    $language = Member::loggedIn()->language()->addToStack(
                        'dtproxy_progress',
                        false,
                        [
                            'sprintf' => [
                                $run['current'],
                                $run['total'],
                            ],
                        ]
                    );

                    return [
                        [
                            'total'    => $run['total'],
                            'current'  => $run['current'],
                            'progress' => $run['progress'],
                        ],
                        $language,
                        $progress,
                    ];
                } else {
                    $progress = ($run['complete'] / $run['tot']) * 100;
                    $language = Member::loggedIn()->language()->addToStack(
                        'dtproxy_progress_extra',
                        false,
                        [
                            'sprintf' => [
                                $run['lastStep'],
                                $run['complete'],
                                $run['tot'],
                            ],
                        ]
                    );

                    return [
                        ['complete' => $run['complete'], 'step' => $run['step']],
                        $language,
                        $progress,
                    ];
                }
            }
        }, function () {
            /* And redirect back to the overview screen */
            $url = Url::internal('app=core&module=overview&controller=dashboard');
            Output::i()->redirect($url, 'dtproxy_done');
        }
        );
    }
}
