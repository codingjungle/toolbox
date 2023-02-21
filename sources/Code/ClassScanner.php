<?php

/**
 * @brief      ClassScanner Class
 * @author     -storm_author-
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox
 * @since      5.1.3
 * @version    -storm_version-
 */

namespace IPS\toolbox\Code;

use Error;
use Exception;
use IPS\Data\Store;
use IPS\Output;
use IPS\Request;
use IPS\toolbox\Code\Utils\ParentVisitor;
use IPS\toolbox\Proxy\Proxyclass;
use OutOfRangeException;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * ClassScanner Class
 * @mixin \IPS\toolbox\Code\ClassScanner
 */
class _ClassScanner extends ParserAbstract
{
    use Scanner;

    /**
     * paths should be relative to <app path>/sources/
     * @var array
     */
    protected array $excludedFolders = [
        'vendor',
        'Vendor',
        'ThirdParty',
        'Thirdparty',
        'thirdparty',
        '3rdParty',
        '3rdparty',
        'Composer',
        'composer'
    ];

    public function validate(): array
    {
        $warnings = [
            'processing' => [],
            'signature' => [],
            'parameters' => [],
            'parentUsage' => [],
            'case' => [],
            'errors' => [],
        ];
        /** @var SplFileInfo $file */
        foreach ($this->files as $file) {
            $content = $file->getContents();
            $tokens = Proxyclass::i()->tokenize($content);
            try {
                if (empty($tokens) === true || $tokens['type'] === T_TRAIT || $tokens['type'] === T_INTERFACE) {
                    continue;
                }
            } catch (Throwable $e) {
            }
            $cs = $tokens['class'];
            $ns = $tokens['namespace'];
            $filename = $file->getFilenameWithoutExtension();

            //lets make sure this is an IPS class!
            if (str_starts_with($cs, '_') === true && str_contains($ns, 'IPS') === true) {
                $first = mb_substr($cs, 1);
                $className = '\\' . $tokens['namespace'] . '\\' . $first;
                if ($filename !== $first) {
                    $currentFileName = str_replace($this->app->getApplicationPath(), '', $file->getRealPath());
                    $warnings['case'][] = [
                        'error' => 'Case Mismatch',
                        'path' => [
                            'url' => $this->buildPath($file->getRealPath(), 0),
                            'name' => $currentFileName
                        ],
                        'class' => $first
                    ];
                }
                //check for case-insensitive/preserving. this wouldn't have been a problem normally, but just recently
                //found out that ext4 in new versions of the linux kernel support "case folding" which is case-preserving
                //i'm gonna say this is MS's influence on the kernel!

                try {
                    $currentClass = new \ReflectionClass($className);
                    $currentClass = $currentClass->getParentClass();
                    //okay this is not a class we are gonna check, as its not a child/subclass
                    if ($currentClass->getParentClass() === false) {
                        continue;
                    }
                    //so we are here, first things first, we have to get the original parent class, we will have to make
                    //a few exceptions here, like if it is an item or node, as they both extend AR
                    //due to IPS monkey patching, we have to traverse till we get to the original parent to check for changes
                    // otherwise we will just be comparing the hook to the hook...
                    $done = false;
                    $pc = $currentClass;
                    while ($done !== true) {
                        $parentClass = $pc->getParentClass();
                        $pc = $parentClass;
                        if ($parentClass instanceof ReflectionClass) {
                            $name = $parentClass->getName();
                            if (!str_contains($name, 'IPS')) {
                                $done = true;
                                //if this is not an IPS class, we need to vamoose
                                continue 2;
                            }

                            //is this one of those classes we don't want to go all the back on?
                            if (isset($this->fullStop[$name]) || $pc->getParentClass() === false) {
                                $done = true;
                            }
                        } else {
                            $done = true;
                        }
                    }

                    foreach ($currentClass->getTraits() as $trait) {
                        $contentTrait = \file_get_contents($trait->getFileName());
                        $this->validationChecks($trait, $parentClass, $contentTrait,$file->getRealPath(), false, $warnings);
                    }
                    $this->validationChecks($currentClass, $parentClass, $content, $file->getRealPath(),false, $warnings);
                } catch (Throwable|Exception|Error $e) {
                    $path = $this->buildPath($file->getRealPath(), $e->getLine());
                    $warnings['processing'][] = [
                        'error' => $e->getMessage(),
                        'path' => ['url' => $path, 'name' => $file->getFilename()],
                    ];
                    continue;
                }
            }
        }
        return $warnings;
    }

    protected function getFiles()
    {
        $files = new Finder();
        $files->in($this->getAppPath() . 'sources/')->name('*.php');
        if (empty($this->skip) === false) {
            $files->notName($this->skip);
        }
        if (empty($this->excludedFolders) === false) {
            $files->exclude($this->excludedFolders);
        }
        $this->files = $files->files();
    }
}
