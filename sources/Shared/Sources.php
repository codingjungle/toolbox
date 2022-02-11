<?php

/**
 * @brief       Sources Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       4.0.1
 * @version     -storm_version-
 */


namespace IPS\toolbox\Shared;


use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\toolbox\Proxy\Generator\Cache;

use function array_shift;
use function explode;
use function implode;
use function ltrim;
use function mb_strtoupper;
use function preg_grep;
use function preg_quote;
use function str_replace;


trait Sources
{
    protected function standard()
    {
        $config = [
            'Namespace',
            'ClassName',
            'Imports',
            'Abstract',
            'Extends',
            'Interfaces',
            'Traits',
        ];

        $this->doOutput($config, 'standard', 'Standard Class');
    }

    protected function doOutput($config, $type, $title)
    {
        $this->elements->buildForm($config, $type);
        $this->elements->create();
        $pageTitle = mb_strtoupper($this->application->directory) . ': ' . $title;
        $url = (string)Url::internal(
            'app=core&module=applications&controller=developer&appKey=' . Request::i()->appKey
        )->csrf();
        unset(Output::i()->breadcrumb['module']);
        if ($this->front === false) {
            Output::i()->breadcrumb[] = [$url, 'Developer Center'];
            Output::i()->breadcrumb[] = [$url, $this->application->directory];
        } else {
            $title = $pageTitle;
        }
        Output::i()->breadcrumb[] = [null, $title];
        Output::i()->title = $pageTitle;

        Output::i()->output = $this->elements->form;
    }

    protected function debug()
    {
        $this->elements->type = 'Debug';
        $this->elements->generate();
        $url = Url::internal(
            'app=core&module=applications&controller=developer&appKey=' . $this->application->directory
        );
        Output::i()->redirect($url, 'Profiler Debug Class Generated');
    }

    protected function memory()
    {
        $this->elements->type = 'Memory';
        $this->elements->generate();
        $url = Url::internal(
            'app=core&module=applications&controller=developer&appKey=' . $this->application->directory
        );
        Output::i()->redirect($url, 'Profiler Memory Class Generated');
    }

    protected function form()
    {
        $this->elements->type = 'Form';
        $this->elements->generate();
        $url = Url::internal(
            'app=core&module=applications&controller=developer&appKey=' . $this->application->directory
        );
        Output::i()->redirect($url->csrf(), 'Form Class Generated');
    }

    protected function cinterface()
    {
        $config = [
            'Namespace',
            'ClassName',
        ];

        $this->doOutput($config, 'interfacing', 'Interface');
    }

    protected function ctraits()
    {
        $config = [
            'Namespace',
            'ClassName',
        ];

        $this->doOutput($config, 'traits', 'Trait');
    }

    protected function singleton()
    {
        $config = [
            'Namespace',
            'ClassName',
            'Imports',
            'Interfaces',
            'Traits',
        ];

        $this->doOutput($config, 'singleton', 'Singleton');
    }

    protected function member()
    {
        $this->elements->type = 'Member';
        $this->elements->generate();
        $url = Url::internal(
            'app=core&module=applications&controller=developer&appKey=' . $this->application->directory
        );
        Output::i()->redirect($url, 'Member Class Generated');
    }

    protected function orm()
    {
        $this->elements->type = 'Orm';
        $this->elements->generate();
        $url = Url::internal(
            'app=core&module=applications&controller=developer&appKey=' . $this->application->directory
        );
        Output::i()->redirect($url, 'ORM Trait Generated');
    }

    protected function ar()
    {
        $config = [
            'Namespace',
            'ClassName',
            'Imports',
            'Database',
            'prefix',
            'scaffolding',
            'Interfaces',
            'Traits',
        ];

        $this->doOutput($config, 'activerecord', 'ActiveRecord Class');
    }

    protected function node()
    {
        $config = [
            'Namespace',
            'ClassName',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'SubNode',
            'ItemClass',
            'NodeInterfaces',
            'NodeTraits',
        ];
        $this->doOutput($config, 'node', 'Node Class');
    }

    protected function item()
    {
        $config = [
            'Namespace',
            'ClassName',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'ItemNodeClass',
            'ItemCommentClass',
            'ItemReviewClass',
            'ItemInterfaces',
            'ItemTraits',
        ];
        $this->doOutput($config, 'item', 'Item Class');
    }

    protected function comment()
    {
        $config = [
            'Namespace',
            'ClassName',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'ContentItemClass',
            'CommentInterfaces',
            'ItemTraits',
        ];
        $this->doOutput($config, 'comment', 'Comment Class');
    }

    protected function review()
    {
        $config = [
            'Namespace',
            'ClassName',
            'Imports',
            'Database',
            'prefix',
            'Scaffolding',
            'ContentItemClass',
            'CommentInterfaces',
            'ItemTraits',
        ];
        $this->doOutput($config, 'review', 'Review Class');
    }

    protected function findClass()
    {
        $classes = Cache::i()->getClasses();

        if (empty($classes) !== true) {
            $input = 'IPS\\' . ltrim(Request::i()->input, '\\');

            $root = preg_quote($input, '#');
            $foo = preg_grep('#^' . $root . '#i', $classes);
            $return = [];
            foreach ($foo as $f) {
                $ogClass = explode('\\', $f);
                array_shift($ogClass);
                $f = implode('\\', $ogClass);
                $return[] = [
                    'value' => $f,
                    'html'  => '\\IPS\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function findClassWithApp()
    {
        $classes = Cache::i()->getClasses();

        if (empty($classes) !== true) {
            $input = 'IPS\\' . Request::i()->appKey . '\\' . ltrim(Request::i()->input, '\\');

            $root = preg_quote($input, '#');
            $foo = preg_grep('#^' . $root . '#i', $classes);
            $return = [];
            foreach ($foo as $f) {
                $return[] = [
                    'value' => str_replace('IPS\\' . Request::i()->appKey . '\\', '', $f),
                    'html'  => '\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function findNamespace()
    {
        $ns = Cache::i()->getNamespaces();

        if (empty($ns) !== true) {
            $input = 'IPS\\' . Request::i()->appKey . '\\' . ltrim(Request::i()->input, '\\');
            $root = preg_quote($input, '#');
            $foo = preg_grep('#^' . $root . '#i', $ns);
            $return = [];
            foreach ($foo as $f) {
                $return[] = [
                    'value' => str_replace('IPS\\' . Request::i()->appKey . '\\', '', $f),
                    'html'  => '\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function findNamespaceHook()
    {
        $ns = Cache::i()->getNamespaces();

        if (empty($ns) !== true) {
            $input = 'IPS\\' . Request::i()->appKey . '\\' . ltrim(Request::i()->input, '\\');
            $root = preg_quote($input, '#');
            $foo = preg_grep('#^' . $root . '#i', $ns);
            $return = [];
            foreach ($foo as $f) {
                $return[] = [
                    'value' => str_replace('IPS\\' . Request::i()->appKey . '\\', '', $f),
                    'html'  => '\\' . $f,
                ];
            }
            Output::i()->json($return);
        }
    }

    protected function api()
    {
        $config = [
            'ClassName',
            'apiType',
        ];
        $this->doOutput($config, 'api', 'API Class');
    }
}
