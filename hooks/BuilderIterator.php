//<?php namespace toolbox_IPS_Application_BuilderIterator_a20575f6940586b8c8c4705c9777af6eb;

use IPS\Plugin;
use IPS\toolbox\DevCenter\extensions\toolbox\DevCenter\Headerdoc\Headerdoc;
use SplFileInfo;

use function file_get_contents;
use function file_put_contents;
use function is_file;
use function mb_strpos;
use function method_exists;
use function register_shutdown_function;
use function tempnam;
use function unlink;

use const IPS\TEMP_DIRECTORY;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class toolbox_hook_BuilderIterator extends _HOOK_CLASS_
{
    /**
     * @inheritdoc
     */
    public function current()
    {
        $file = $this->key();
        $file = \IPS\ROOT_PATH . '/applications/' . $this->application->directory . '/' . $file;
        $path = new SplFileInfo($this->key());
        if (is_file($file) && (mb_strpos($file, '3rdparty') === false || mb_strpos(
                    $file,
                    '3rd_party'
                ) === false || mb_strpos($file, 'vendor') === false) && $path->getExtension() === 'php') {
                    $temporary = tempnam(TEMP_DIRECTORY, 'IPS');
                    if (mb_strpos($path->getPath(), 'hooks') !== false) {
                        $contents = Plugin::addExceptionHandlingToHookFile($file); 
                    } else {
                        $contents = file_get_contents($file);
                    }
                    if (\IPS\toolbox\DevCenter\Headerdoc::i()->can($this->application)) {
                        /* @var Headerdoc $class */
                        foreach ($this->application->extensions('toolbox', 'Headerdoc', true) as $class) {
                            if (method_exists($class, 'finalize')) {
                                $contents = $class->finalize($contents, $this->application);
                            }
                        }
                    }
                    file_put_contents($temporary, $contents);
                    register_shutdown_function(
                        function ($temporary) {
                            unlink($temporary);
                        },
                        $temporary
                    );

                    return $temporary;
                }

        return $file;
    }
}
