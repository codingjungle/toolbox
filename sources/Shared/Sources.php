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

use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Proxy\Generator\Cache;

use function array_shift;
use function explode;
use function implode;
use function ltrim;
use function preg_grep;
use function preg_quote;
use function str_replace;

trait Sources
{
    protected $alt;

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
        $return = $this->elements->create();

        $output = Theme::i()->getTemplate('generator', 'toolbox', 'front')->wrapper($title, $this->elements->form);

        if ($this->elements->form->valuesError === true) {
            $alt = $this->alt ?? $type;
            Output::i()->output = Theme::i()->getTemplate('generator', 'toolbox', 'front')->sources(
                $this->application->directory,
                \IPS\toolbox\DevCenter\Sources::processedSubMenus()['sources'],
                'devcenter',
                'sources',
                $alt,
                $output
            );
        } elseif ($return === null) {
            Output::i()->output = $output;
        } else {
            Output::i()->json(['msg' => $return, 'type' => 'dtsources']);
        }
    }

    protected function doDone($msg)
    {
        Output::i()->output = '<div class="ipsMessage ipsMessage_info">' . $msg . '</div>';
    }

    protected function debug()
    {
        $this->elements->type = 'Debug';
        $this->doDone($this->elements->generate());
    }

    protected function memory()
    {
        $this->elements->type = 'Memory';
        $this->doDone($this->elements->generate());
    }

    protected function form()
    {
        $this->elements->type = 'Form';
        $this->doDone($this->elements->generate());
    }

    protected function settings()
    {
        $this->elements->type = 'Settings';
        $this->doDone($this->elements->generate());
    }

    protected function orm()
    {
        $this->elements->type = 'Orm';
        $this->doDone($this->elements->generate());
    }

    protected function member()
    {
        $this->elements->type = 'Member';
        $this->doDone($this->elements->generate());
    }

    protected function cinterface()
    {
        $this->alt = 'cinterface';
        $config = [
            'Namespace',
            'ClassName',
        ];

        $this->doOutput($config, 'interfacing', 'Interface');
    }

    protected function ctraits()
    {
        $this->alt = 'ctraits';
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
        $type = Request::i()->type ?? 'class';
        if ($type === 'interface') {
            $classes = Cache::i()->getInterfaces();
        } elseif ($type === 'trait') {
            $classes = Cache::i()->getTraits();
        } else {
            $classes = Cache::i()->getClasses();
        }
        if (empty($classes) !== true) {
            $input = ltrim(Request::i()->input, '\\');

            $root = preg_quote($input, '#');
            $foo = preg_grep('#' . $root . '#i', $classes);
            $return = [];
            foreach ($foo as $f) {
                $ogClass = explode('\\', $f);
                array_shift($ogClass);
                $f = implode('\\', $ogClass);
                $return[] = [
                    'value' => '\\IPS\\' . $f,
                    'html' => '\\IPS\\' . $f,
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

    protected function manage()
    {
        $menus = \IPS\toolbox\DevCenter\Sources::processedSubMenus();
        Output::i()->output = Theme::i()
            ->getTemplate('generator', 'toolbox', 'front')
            ->sources($this->application->directory, $menus['sources'], 'devcenter', 'sources', 'standard');
    }
}
