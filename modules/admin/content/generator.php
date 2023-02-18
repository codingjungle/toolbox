<?php

/**
 * @brief       Generator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Content Generator
 * @since       1.0.3
 * @version     -storm_version-
 */

namespace IPS\toolbox\modules\admin\content;

use Exception;
use InvalidArgumentException;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\toolbox\Content\Club;
use IPS\toolbox\Content\Forum;
use IPS\toolbox\Content\Generator;
use IPS\toolbox\Content\Member as Dtmember;
use IPS\toolbox\Content\Post;
use IPS\toolbox\Content\Topic;
use IPS\toolbox\Form;

use IPS\toolbox\Proxy\Helpers\Store;

use function defined;
use function header;
use function time;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * _generator
 */
class _generator extends Controller
{
    /**
     * @brief    Has been CSRF-protected
     */
    public static $csrfProtected = true;

    /**
     * @inheritdoc
     * @throws Exception
     */
    protected function manage()
    {
        $groups = [];

        /* @var Group $group */
        foreach (Group::groups() as $key => $group) {
            $groups[$key] = $group->get_formattedName();
        }

        $url = $this->url->setQueryString(['do' => 'delete', 'oldDo' => Request::i()->do]);

        Output::i()->sidebar['actions']['delete'] = [
            'icon'  => 'delete',
            'title' => 'Delete Content Generated Data',
            'link'  => $url,

        ];
        $form = Form::create()->setPrefix('dtcontent_');
        $form->setDbPrefix(false)->removePrefix();
        $form->addElement('type', 'select')->options([
            'options' => [
                'none'   => 'Select Type',
                'member' => 'Member',
                'forum'  => 'Forum',
                'topic'  => 'Topic',
                'post'   => 'Post',
                'club'   => 'Club',
            ],
        ])->toggles([
            'member' => [
                'passwords',
                'group',
                'club',
                'rangeStart',
//                'rangeEnd',
            ],
            'topic'  => [
                'rangeStart',
//                'rangeEnd',
            ],
        ])->validation(static function ($data) {
            if ($data === 'none') {
                throw new InvalidArgumentException('dtcontent_gen_none');
            }
        });
        $form->addElement('limit', 'number')->empty(50)->options(['min' => 1]);
        $form->addElement('rangeStart', 'date')->empty(Settings::i()->getFromConfGlobal('board_start'));
//        $form->add('rangeEnd', 'date')->empty(time());
        $form->addElement('passwords', 'yn');
        $form->addElement('club', 'yn')->options(['disabled' => !Settings::i()->clubs]);
        $form->addElement('group', 'select')
             ->empty(Settings::i()->getFromConfGlobal('member_group'))
             ->options(['options' => $groups]);

        if ($values = $form->values()) {
            $url = $this->url;
            $query = [
                'type'  => $values['type'] ,
                'limit' => $values['limit'],
            ];

            if ($values['type'] === 'members') {
                $query['password'] = $values['passwords'];
                $query['group'] = $values['group'];
                $query['club'] = $values['club'];
            }
            /* @var DateTime $start */
            $start = $values['rangeStart'];
            \IPS\Data\Store::i()->toolbox_times = $start instanceof \DateTime ? $start->getTimestamp() : 0;

            $query['do'] = 'queue';
            Output::i()->redirect($url->setQueryString($query)->csrf());
        }

        Output::i()->title = 'Generate Dummy Data';
        Output::i()->output = $form;
    }

    /**
     * delete the generated content
     */
    protected function delete()
    {
        Output::i()->title = 'Delete Content';

        $url = $this->url->setQueryString(['do' => 'delete', 'oldDo' => Request::i()->oldDo]);
        Output::i()->output = new MultipleRedirect($url, static function ($data) {
            $offset = 0;
            if (isset($data['offset'])) {
                $offset = $data['offset'];
            }

            if (!isset($data['total'])) {
                try {
                    $total = Db::i()->select('COUNT(*)', 'toolbox_generator')->first();
                } catch (Exception $e) {
                    $total = 0;
                }
            } else {
                $total = $data['total'];
            }

            $limit = 100;

            $count = Db::i()->select('COUNT(*)', 'toolbox_generator')->first();
            if (!$count) {
                return null;
            }

            $sql = Db::i()->select('*', 'toolbox_generator', [], 'generator_id ASC', $limit);

            $contents = new ActiveRecordIterator($sql, Generator::class);

            /* @var Generator $content */
            foreach ($contents as $content) {
                $content->process();
                $offset++;
            }

            $progress = ($offset / $total) * 100;

            $language = Member::loggedIn()->language()->addToStack('dtcontent_progress', false, [
                'sprintf' => [
                    $offset,
                    $total,
                ],
            ]);

            return [
                ['total' => $total, 'offset' => $offset],
                $language,
                $progress,
            ];
        }, function () {
            /* And redirect back to the overview screen */
            $url = Url::internal('app=toolbox&module=content&controller=generator');
            Output::i()->redirect($url, 'dtcontent_generation_delete_done');
        });
    }

    /**
     * executes a MR for the generator
     */
    protected function queue()
    {
        Output::i()->title = 'Generator';
        $type = Request::i()->type ?: 'forums';
        $limit = Request::i()->limit ?: 10;
        $password = Request::i()->password ?: null;
        $group = Request::i()->group ?: null;
        $club = Request::i()->club ?: null;

        $url = $this->url->setQueryString([
            'do'       => 'queue',
            'type'     => $type,
            'limit'    => $limit,
            'password' => $password,
            'group'    => $group,
            'club'     => $club,
        ]);

        Output::i()->output = new MultipleRedirect($url, function ($data) {
            $offset = 0;
            $type = Request::i()->type ?: 'forums';
            $limit = Request::i()->limit ?: 10;
            $password = Request::i()->password ?: null;
            $group = Request::i()->group ?: null;
            $club = Request::i()->club ?: null;

            if (isset($data['offset'])) {
                $offset = $data['offset'];
            }

            if (isset($data['limit'])) {
                $limit = $data['limit'];
            }

            if (isset($data['type'])) {
                $type = $data['type'];
            }

            if (isset($data['password'])) {
                $password = $data['password'];
            }

            if (isset($data['group'])) {
                $group = $data['group'];
            }

            if (isset($data['club'])) {
                $club = $data['club'];
            }

            $max = 200;

            if($offset === 0){
                $max = 1;
            }

            if ($limit < $max) {
                $max = $limit;
            }

            if ($offset >= $limit) {
                return null;
            }

            for ($i = 0; $i < $max; $i++) {
                switch ($type) {
                    case 'member':
                        $member = new Dtmember();
                        $member->build($password, $group);
                        break;
                    case 'forum':
                        (new Forum())->build();
                        break;
                    case 'topic':
                        $topic = new Topic();
                        $topic->build();
                        break;
                    case 'post':
                        (new Post())->build();
                        break;
                    case 'club':
                        (new Club())->build();
                        break;
                }
                $offset++;
            }

            $progress = ($offset / $limit) * 100;

            $language = Member::loggedIn()->language()->addToStack('dtcontent_progress', false, [
                'sprintf' => [
                    $offset,
                    $limit,
                ],
            ]);

            return [
                [
                    'type'     => $type,
                    'limit'    => $limit,
                    'offset'   => $offset,
                    'password' => $password,
                    'group'    => $group,
                    'club'     => $club
                ],
                $language,
                $progress,
            ];
        }, function () {
            $url = Url::internal('app=toolbox&module=content&controller=generator');
            $lang = Member::loggedIn()
                          ->language()
                          ->addToStack('dtcontent_completed', false, ['sprintf' => [mb_ucfirst(Request::i()->type)]]);
            Member::loggedIn()->language()->parseOutputForDisplay($lang);
            Output::i()->redirect($url, $lang);
        });
    }
}
