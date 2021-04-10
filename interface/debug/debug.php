<?php

use IPS\Db;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Session\Front;
use IPS\Theme;
use IPS\toolbox\Profiler\Debug;

require_once str_replace('applications/dtprofiler/interface/debug/debug.php', '',
        str_replace('\\', '/', __FILE__)) . 'init.php';
Front::i();

$max = (ini_get('max_execution_time') / 2) - 5;
$time = time();

while (true) {
    $ct = time() - $time;
    if ($ct >= $max) {
        Output::i()->json(['end' => 1]);
    }

    $query = Db::i()->select('*', 'toolbox_debug', ['debug_ajax = ?', 1], 'debug_id DESC');

    if (count($query)) {
        $iterators = new ActiveRecordIterator($query, Debug::class);

        foreach ($iterators as $obj) {
            if ($obj->type === 'exception' || $obj->type === 'array') {
                $message = json_decode($obj->log, true);
                $list[] = Theme::i()->getTemplate('generic', 'dtprofiler', 'front')->keyvalue($message, $obj->key);
            } else {
                $list[] = Theme::i()->getTemplate('generic', 'dtprofiler', 'front')->string($obj->log, $obj->key);
            }
            $obj->delete();
        }

        $return = [];
        if (is_array($list) && count($list)) {
            $count = count($list);
            $return['count'] = $count;
            $return['items'] = $list;
            $return['whole'] = Theme::i()
                                    ->getTemplate('generic', 'dtprofiler', 'front')
                                    ->button('Debug', 'debug', 'List of debug messages', $list, $count, 'bug');
        }

        if (is_array($return) and count($return)) {
            Output::i()->json($return);
        }
    } else {
        sleep(1);
        continue;
    }
}
