<?php

/**
* @brief      Versions Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.8
* @version    -storm_version-
*/

namespace IPS\toolbox\Build;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Application\BuilderIterator;
use IPS\Data\Store;
use IPS\toolbox\Build\Versions;

use IPS\toolbox\Profiler\Debug;
use IPS\toolbox\Slasher;
use Phar;
use PharData;
use RuntimeException;

use function _p;
use function array_key_last;
use function array_walk;
use function chmod;
use function defined;
use function is_dir;
use function is_numeric;
use function mkdir;
use function preg_match;
use function rtrim;
use function sprintf;
use function str_replace;
use function str_split;

use const DT_SLASHER;
use const IPS\IPS_FOLDER_PERMISSION;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Versions Class
* @mixin Versions
*/
class _Versions
{

    /** @var Application */
    public Application $app;
    /**
     * @var string
     */
    public string $semVer;
    /**
     * @var int
     */
    public int $longVer;
    protected array $segmentedLong;
    protected bool $bumpLong;
    protected int $major;
    protected int $minor;
    protected int $patch;
    public $preRelease;
    public $buildMetaData;
    public int $alpha;
    public int $beta;
    public int $rc;
    protected ?string $bumpType;
    public bool $isAlpha = false;
    public bool $isBeta = false;
    public bool $isRc = false;
    protected int $increment = 1;
    public string $path;
    protected bool $slasher;
    protected array $values;
    public $error;
    protected $originalLongVersion;
    /**
    * _Versions constructor
    *
    */
    public function __construct(string $app,array $data){
        $this->values = $data;
        $bumpType = $data['bumpType'] ?? 'manual';
        $long = $data['long'] ?? null;
        $short = $data['short'] ?? null;
        $application = Application::load($app);
        $preRelease = $data['prerelease'] ?? null;
        if($bumpType !== 'manual'){
            $long = null;
            $short = null;
        }
        $this->bumpType = $bumpType;
        $this->slasher = isset($data['slasher']) && $data['slasher'];
        $this->app = $application;
        $this->semVer = $short ? $short : ($application->version ? trim($application->version) : '0.0.0-alpha.1');
        $this->longVer = $long ? $long : ($application->long_version ? (int)$application->long_version : 10000);
        $this->originalLongVersion = $application->long_version ? (int)$application->long_version : 10000;
        $this->segmentedLong = $this->split($this->longVer);
        $this->bumpLong = $long === null;
        preg_match('#^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<preRelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildMetaData>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$#',$this->semVer,$matches);
        foreach($matches as $key => $data){
            if(!is_numeric($key)){
                $this->{$key} = $data;
            }
        }
        if($preRelease !== null){
            $this->{$preRelease} = true;
        }
    }

    protected function split(int $toSplit){
        $split = str_split($toSplit);
        array_walk($split, static function(&$value){ $value = (int) $value;});
        return $split;
    }

    protected function bumpVersion(){

        switch($this->bumpType){
            case 'major':
                $this->major++;
                $this->minor = 0;
                $this->patch = 0;
                $second = null;
                if($this->major >= 10 ) {
                    $second = 1;
                    if($this->major === $this->segmentedLong[0].$this->segmentedLong[1]) {
                        $major = $this->split($this->major);
                        $this->segmentedLong[0] = $major[0];
                        $this->segmentedLong[1] = $major[1];
                    }
                }
                else{
                    $this->segmentedLong[0] = $this->major;
                }
                foreach($this->segmentedLong as $key => $value){
                    if($key === 0 || ($second !== null && $second === $key)){
                        continue;
                    }
                    $this->segmentedLong[$key] = 0;
                }
                $this->longVer = implode('',$this->segmentedLong);
                $this->segmentedLong = $this->split($this->longVer);
                break;
            case 'minor':
                $this->minor++;
                $this->patch = 0;
                $this->longVer++;
                $this->segmentedLong = $this->split($this->longVer);
                break;
            case 'patch':
                $this->patch++;
                $this->longVer++;
                $this->segmentedLong = $this->split($this->longVer);
                break;
        }

        if($this->bumpType === 'manual' && (!($this->longVer >= $this->originalLongVersion) ||$this->isAlpha === true || $this->isBeta === true || $this->isRc)){
            $this->longVer++;
            $this->segmentedLong = $this->split($this->longVer);
        }

        if($this->preRelease){
            preg_match('#(.*?)\.(\d+)#', $this->preRelease, $matches);
            if(isset($matches[1])){
                $type = 'is'.ucfirst(\mb_strtolower($matches[1]));
                if($this->{$type}){
                    if(isset($matches[2]) ){
                        $this->increment = $matches[2] + 1;
                    }
                }
            }
        }

        $this->semVer = $this->major.'.'.$this->minor.'.'.$this->patch;

        if($this->isAlpha){
            $this->semVer .= '-alpha.'.$this->increment;
        }
        elseif($this->isBeta){
            $this->semVer .= '-beta.'.$this->increment;
        }
        elseif($this->isRc){
            $this->semVer .= '-rc.'.$this->increment;
        }

        $this->path = Application::getRootPath('core') . '/exports/' . $this->app->directory . '/' . $this->longVer . '/';

    }

    public function getValues(){
        return $this->values;
    }

    public function build(){
        $this->bumpVersion();
        $pharPath = null;
        try {
            $savePath = $this->path;
            $filename = $this->app->directory . ' - ' . $this->semVer;
            $skippedFiles = Slasher::i()->defaultFiles;
            $skippedDirs = Slasher::i()->defaultDirectories;
            /* @var \IPS\toolbox\Profiler\MyApps $extension */
            foreach ($this->app->extensions('toolbox', 'MyApps') as $extension) {
                 $extension->beforeBuild($this, $filename, $savePath );
                 $extension->slasherDirExclude($skippedDirs);
                 $extension->slasherFileExclude($skippedFiles);
            }
            $this->path = $savePath;
            if($this->slasher === true) {
                Slasher::i()->start(
                    $this->app,
                    $skippedDirs,
                    $skippedFiles
                );
            }
            try {
                $this->app->long_version = $this->longVer;
                $this->app->version = $this->semVer;

                $this->app->save();
                unset(Store::i()->applications);

                $this->app->assignNewVersion($this->longVer, $this->semVer);
                $this->app->build();
                $this->app->save();

                if(isset($this->values['download']) && $this->values['download']){
                    $pharPath	= str_replace( '\\', '/', rtrim( \IPS\TEMP_DIRECTORY, '/' ) ) . '/' . $filename . ".tar";
                }
                else {
                    if (!is_dir($savePath)) {
                        if (!mkdir($savePath, IPS_FOLDER_PERMISSION, true) && !is_dir($savePath)) {
                            throw new RuntimeException(sprintf('Directory "%s" was not created', $savePath));
                        }
                        chmod($savePath, IPS_FOLDER_PERMISSION);
                    }
                    $pharPath = $savePath . $filename . '.tar';
                }
                $download = new PharData($pharPath, 0, $this->app->directory . '.tar', Phar::TAR);
                $download->buildFromIterator(new BuilderIterator($this->app));
            } catch (Exception $e) {
                $this->error = $e->getMessage();
                Debug::log($e, 'phar');
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            Debug::log($e, 'phar');
        }

        unset(Store::i()->applications, $download);
        return $pharPath;
    }

}