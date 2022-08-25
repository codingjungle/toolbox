<?php

/**
 * @brief       Dtbase Settings extension: Profiler
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Profiler
 * @since       1.1.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\extensions\toolbox\Settings;

use IPS\Member;
use IPS\Settings;
use IPS\toolbox\Form;

use function _p;
use function defined;
use function header;
use function json_decode;
use function json_encode;

/* To prevent PHP errors (extending class does not exist) revealing path */

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * profiler
 */
class _profiler
{

    /**
     * add in array of form helpers
     *     * @param Form $form
     */
    public function elements(&$form): void
    {
        $members = null;
        if (empty(Settings::i()->dtprofiler_can_use) !== true) {
            $users = json_decode(Settings::i()->dtprofiler_can_use, true);
            foreach ($users as $user) {
                $members[] = Member::load($user);
            }
        }
        $form->addTab('dtprofiler');
        $form->addElement('dtprofiler_can_use', 'member')->value($members)->options(['multiple' => 10]);
        $form->addElement('dtprofiler_show_admin', 'yn');
        $form->addHeader('dtprofiler_profiler_tabs');
        $form->addElement('dtprofiler_enabled_execution', 'yn');
        $form->addElement('dtprofiler_enabled_executions', 'yn');
        $form->addElement('dtprofiler_enabled_memory', 'yn');
        $form->addElement('dtprofiler_enabled_memory_summary', 'yn');
        $form->addElement('dtprofiler_enabled_files', 'yn');
        $form->addElement('dtprofiler_enabled_enivro', 'yn');
        $form->addElement('dtprofiler_enabled_templates', 'yn');
        $form->addElement('dtprofiler_enabled_css', 'yn');
        $form->addElement('dtprofiler_enabled_js', 'yn');
        $form->addElement('dtprofiler_enabled_jsvars', 'yn');
        $form->addElement('dtprofiler_enable_debug', 'yn')->toggles(['dtprofiler_enable_debug_ajax']);
        $form->addElement('dtprofiler_enable_debug_ajax', 'yn');
        $form->addElement('dtprofiler_enabled_logs', 'yn');
        $form->addElement('dtprofiler_logs_amount', '#');
        $form->addElement('dtprofiler_git_data', 'yn');
        $form->addElement('dtprofiler_show_changes', 'yn');
        $form->addElement('dtprofiler_use_console','yn');
        $form->addElement('dtprofiler_replace_console','yn')->toggles(['dtprofiler_console_replacements']);
        $data = [
            'log',
            'table',
            'assert',
            'clear',
            'count',
            'error',
            'group',
            'groupCollapsed',
            'groupEnd',
            'info',
            'time',
            'timeEnd',
            'trace',
            'warn'
        ];
         $vals = json_decode(Settings::i()->dtprofiler_console_replacements,true);
        $options = array_combine(array_values($data),array_values($data));
        $form->addElement('dtprofiler_console_replacements','cbs')->options(['options' => $options])->value($vals);

        $form->addTab('code_analyzer');
        Member::loggedIn()->language()->words['code_analyzer_tab'] = 'Code Analyzer';
        $options = [
            'dtcode_analyze_db' => 'Database',
            'dtcode_analyze_error_codes' => 'Error Codes',
            'dtcode_analyze_filestorage' => 'File Storage',
            'dtcode_analyze_hooks' => 'Hooks',
            'dtcode_analyze_interface' => 'Interface Folder',
            'dtcode_analyze_langs_check' => 'Language Strings Check',
            'dtcode_analyze_langs_verify' => 'Language Strings Verify',
            'dtcode_analyze_rootpath' => 'Rootpath Usage Check',
            'dtcode_analyze_settings_check' => 'Settings Usage Check',
            'dtcode_analyze_settings_verify' => 'Settings Usage Verify'
        ];
        foreach($options as $key => $label){
            $form->addElement($key,'yn')->label($label);
        }
    }

    /**
     * formValues, format the values before saving as settings
     *
     * @param array $values
     *
     * @return void
     */
    public function formatValues(&$values): void
    {
        $new = [];
        if (empty($values['dtprofiler_can_use']) !== true) {
            foreach ($values['dtprofiler_can_use'] as $key => $value) {
                $new[] = $value->member_id;
            }

            $values['dtprofiler_can_use'] = json_encode($new);
        } else {
            $values['dtprofiler_can_use'] = null;
        }

        if(empty($values['dtprofiler_console_replacements']) === false){
            $values['dtprofiler_console_replacements'] = json_encode($values['dtprofiler_console_replacements']);
        }
        else{
            $values['dtprofiler_console_replacements'] = null;
        }

    }

}
