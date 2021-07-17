//<?php namespace toolbox_IPS_Helpers_Form_FormAbstract_a3ff6a96b40f2e8c984d8d91aaafcf776;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Member;
use IPS\Theme;

use function defined;
use function is_array;

if ( !defined('\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

abstract class toolbox_hook_FormAbstract extends _HOOK_CLASS_
{
    public function rowHtml( $form=NULL )
    {
        if( defined('TOOLBOXDEV') && TOOLBOXDEV === true) {
            try {
                if ($this->label) {
                    $label = $this->label;
                } else {
                    $label = $this->name;
                    if (isset($this->options['labelSprintf'])) {
                        $label = Member::loggedIn()->language()->addToStack(
                            $label,
                            false,
                            ['sprintf' => $this->options['labelSprintf']]
                        );
                    } else {
                        $label = isset($this->options['labelHtmlSprintf']) ? Member::loggedIn()->language()->addToStack(
                            $label,
                            false,
                            ['htmlsprintf' => $this->options['labelHtmlSprintf']]
                        ) : Member::loggedIn()->language()->addToStack($label);
                    }
                }

                $html = $this->html();

                if ($this->description) {
                    $desc = $this->description;
                } else {
                    $desc = $this->name . '_desc';
                    $desc = Member::loggedIn()->language()->addToStack(
                        $desc,
                        false,
                        [
                            'returnBlank' => true,
                            'returnInto'  => Theme::i()
                                                       ->getTemplate(
                                                           'forms',
                                                           'core',
                                                           'global'
                                                       )
                                                       ->rowDesc(
                                                           $label,
                                                           $html,
                                                           $this->appearRequired,
                                                           $this->error,
                                                           $this->prefix,
                                                           $this->suffix,
                                                           $this->htmlId ?: ($form ? "{$form->id}_{$this->name}" : null),
                                                           $this,
                                                           $form
                                                       )
                        ]
                    );
                }

                if ($this->warningBox) {
                    $warning = $this->warningBox;
                } else {
                    $warning = $this->name . '_warning';
                    $warning = Member::loggedIn()->language()->addToStack(
                        $warning,
                        false,
                        [
                            'returnBlank' => true,
                            'returnInto'  => Theme::i()
                                                       ->getTemplate(
                                                           'forms',
                                                           'core',
                                                           'global'
                                                       )
                                                       ->rowWarning(
                                                           $label,
                                                           $html,
                                                           $this->appearRequired,
                                                           $this->error,
                                                           $this->prefix,
                                                           $this->suffix,
                                                           $this->htmlId ?: ($form ? "{$form->id}_{$this->name}" : null),
                                                           $this,
                                                           $form
                                                       )
                        ]
                    );
                }

                if (array_key_exists('endSuffix', $this->options)) {
                    $this->suffix = $this->options['endSuffix'];
                }

                /* Some elements support an array for suffix, such as Number which supports preUnlimited and postUnlimited. We need to wipe out
                    the suffix here before calling the row() template, however, which only supports a string and throws an Array to string conversion error.
                    By this point, the element template has already ran and used the suffix if designed to */
                if (is_array($this->suffix)) {
                    $this->suffix = '';
                }

                return Theme::i()->getTemplate('forms', 'core')->row(
                    $label,
                    $html,
                    $desc,
                    $warning,
                    $this->appearRequired,
                    $this->error,
                    $this->prefix,
                    $this->suffix,
                    $this->htmlId ?: ($form ? "{$form->id}_{$this->name}" : null),
                    $this,
                    $form,
                    $this->rowClasses
                );
            } catch (Exception $e) {
                throw $e;
            }
        }
        return parent::rowHtml($form);
    }

}
