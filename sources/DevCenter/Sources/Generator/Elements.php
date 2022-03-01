<?php

/**
 * @brief       Form Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.3.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\DevCenter\Sources\Generator;

use InvalidArgumentException;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Ftp;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\KeyValue;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Poll;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Rating;
use IPS\Helpers\Form\Search;
use IPS\Helpers\Form\SocialGroup;
use IPS\Helpers\Form\Sort;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Tel;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Timezone;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\toolbox\Application;

class _Elements extends GeneratorAbstract
{
    protected $includeConstructor = false;

    public function bodyGenerator()
    {
        $this->brief = 'Class';
        $this->generator->addImport(InvalidArgumentException::class);
        $this->generator->addImport(Address::class);
        $this->generator->addImport(Captcha::class);
        $this->generator->addImport(CheckboxSet::class);
        $this->generator->addImport(Checkbox::class);
        $this->generator->addImport(Codemirror::class);
        $this->generator->addImport(Color::class);
        $this->generator->addImport(Custom::class);
        $this->generator->addImport(Date::class);
        $this->generator->addImport(DateRange::class);
        $this->generator->addImport(Editor::class);
        $this->generator->addImport(Email::class);
        $this->generator->addImport(FormAbstract::class);
        $this->generator->addImport(Ftp::class);
        $this->generator->addImport(Interval::class);
        $this->generator->addImport(\IPS\Helpers\Form\Item::class);
        $this->generator->addImport(KeyValue::class);
        $this->generator->addImport(Matrix::class);
        $this->generator->addImport(\IPS\Helpers\Form\Member::class);
        $this->generator->addImport(\IPS\Helpers\Form\Node::class);
        $this->generator->addImport(Number::class);
        $this->generator->addImport(Password::class);
        $this->generator->addImport(Poll::class);
        $this->generator->addImport(Radio::class);
        $this->generator->addImport(Rating::class);
        $this->generator->addImport(Search::class);
        $this->generator->addImport(Select::class);
        $this->generator->addImport(SocialGroup::class);
        $this->generator->addImport(Sort::class);
        $this->generator->addImport(Stack::class);
        $this->generator->addImport(Tel::class);
        $this->generator->addImport(Text::class);
        $this->generator->addImport(TextArea::class);
        $this->generator->addImport(Timezone::class);
        $this->generator->addImport(Translatable::class);
        $this->generator->addImport(Upload::class);
        $this->generator->addImport(Url::class);
        $this->generator->addImport(WidthHeight::class);
        $this->generator->addImport(YesNo::class);

        $this->generator->addImportFunction('array_merge');
        $this->generator->addImportFunction('array_pop');
        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('explode');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('is_array');
        $this->generator->addImportFunction('mb_strtolower');

        $this->generator->addClassComments('@property-read string $name');
        $this->generator->addClassComments('@property-read string $type');
        $this->generator->addClassComments('@property-read string|int|array $value');
        $this->generator->addClassComments('@property-read bool $required');
        $this->generator->addClassComments('@property-read array $options');
        $this->generator->addClassComments('@property-read callable $validationCallback');
        $this->generator->addClassComments('@property-read string $prefix');
        $this->generator->addClassComments('@property-read string $suffix');
        $this->generator->addClassComments('@property-read string $id');
        $this->generator->addClassComments('@property-read string $tab');
        $this->generator->addClassComments('@property-read bool $skip');
        $this->generator->addClassComments('@property-read string $header');
        $this->generator->addClassComments('@property-read bool $appearRequired');
        $this->generator->addClassComments('@property-read array $toggles');
        $this->generator->addClassComments('@property-read array $label');
        $this->generator->addClassComments('@property-read array $description');
        $this->generator->addClassComments('@property-read array $extra');
        $this->generator->addClassComments('@property-read string $sidebar');
        $this->generator->addClassComments('@property-read FormAbstract|string|null $class');
        $this->generator->addClassComments('@property-read bool $custom');

        $this->generator->addClassBody(\file_get_contents(Application::getRootPath('toolbox').'/applications/toolbox/data/defaults/elements.txt') );
    }
}
