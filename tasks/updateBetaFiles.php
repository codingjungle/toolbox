<?php
/**
 * @brief		updateBetaFiles Task
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	toolbox
 * @since		22 Jul 2022
 */

namespace IPS\toolbox\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Log;
use SplFileInfo;
use ApiException;
use IPS\Http\Url;
use IPS\Settings;
use IPS\Session\Front;
use IPS\Dispatcher\Build;
use IPS\toolbox\Api\Beta;
use IPS\toolbox\Application;
use Symfony\Component\Finder\Finder;

use function trim;
use function defined;
use function explode;
use function in_array;
use function strtotime;
use function preg_match;
use function file_exists;
use function json_decode;
use function json_encode;
use function str_replace;
use function str_contains;
use function mb_strtolower;
use function file_get_contents;
use function file_put_contents;

use const DT_BETA_URL;
use const DT_BETA_AUTHOR;
use const DT_BETA_ALLOWED;
use const DT_BETA_CATEGORY;
use const DT_BETA_CLIENT_ID;
use const JSON_PRETTY_PRINT;
use const DT_BETA_DISALLOWED;
use const DT_BETA_CLIENT_SECRET;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * updateBetaFiles Task
 */
class _updateBetaFiles extends \IPS\Task
{
    /**
     * Execute
     *
     * If ran successfully, should return anything worth logging. Only log something
     * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
     * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
     * Tasks should execute within the time of a normal HTTP request.
     *
     * @return    mixed    Message to log or NULL
     * @throws    \IPS\Task\Exception
     */
    public function execute()
    {
        if (defined('DT_BETA_UPLOAD') && DT_BETA_UPLOAD) {
            $_SERVER['HTTP_HOST'] = Settings::i()->base_url;
            $_SERVER['QUERY_STRING'] = '';
            $_SERVER['REQUEST_URI'] = '';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            Build::i();
            Front::i();
            Application::loadAutoLoader();

            $category = DT_BETA_CATEGORY;
            $author = DT_BETA_AUTHOR;
            $response = Beta::i()->get('downloads/files', [ 'categories' => $category ]);

            foreach ($response['results'] as $result) {
                $existing[$result['title']] = [
                    'id' => $result['id'],
                    'time' => strtotime($result['updated'] ?? $result['date'])
                ];
            }

            $finder = new Finder();
            $finder->in(\IPS\Application::getRootPath('core') . '/exports/');
            $filter = function (SplFileInfo $file) {
                if (!in_array($file->getExtension(), ['tar'], true)) {
                    return false;
                }
                return true;
            };
            $new = [];
            $update = [];
            $files = $finder->filter($filter)->files();
            $skip = explode(',', DT_BETA_ALLOWED);
            $disregard = explode(',', DT_BETA_DISALLOWED);
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            foreach ($files as $file) {
                $ft = str_replace('.tar', '', $file->getFilename());

                preg_match('#^(.*?)-(.*?)$#', $ft, $match);

                $name = trim(mb_strtolower($match[1]));
                $version = trim($match[2]);
                if (
                    !in_array($name, $skip, true) &&
                    !str_contains(mb_strtolower($version), 'alpha') &&
                    !str_contains(mb_strtolower($version), 'beta') &&
                    !str_contains(mb_strtolower($version), 'rc')
                ) {
                    continue;
                }

                if (in_array($name, $disregard, true)) {
                    continue;
                }

                $continue = true;
                $time = $file->getMTime();

                if (
                    isset($existing[$name]) &&
                    $time > $existing[$name]['time']
                ) {
                    $oldTime = isset($existing[$name]) ? $existing[$name]['time'] : $update[$name]['time'] ?? null;

                    if (!isset($update[$name])) {
                        if ($oldTime !== null && $time > $oldTime) {
                            $continue = false;
                        } elseif ($oldTime === null) {
                            $continue = false;
                        }
                    }

                    if ($continue === true) {
                        continue;
                    }

                    $update[$name] = [
                        'id' => $existing[$name]['id'],
                        'title' => $name,
                        'save' => 1,
                        'date' => \IPS\DateTime::ts($time)->format('c'),
                        'time' => $time,
                        'version' => $version,
                        'files' => [
                            $file->getFilename() => $file->getContents()
                        ]
                    ];
                } else {
                    $oldTime = isset($existing[$name]) ? $existing[$name]['time'] : $new[$name]['time'] ?? null;
                    if (!isset($new[$name])) {
                        if ($oldTime !== null && $time > $oldTime) {
                            $continue = false;
                        } elseif ($oldTime === null) {
                            $continue = false;
                        }
                    }

                    if ($continue === true) {
                        continue;
                    }

                    $new[$name] = [
                        'category' => $category,
                        'author' => $author,
                        'title' => $name,
                        'description' => $name . ' Testing',
                        'date' => \IPS\DateTime::ts($time)->format('c'),
                        'time' => $time,
                        'version' => $version,
                        'files' => [
                            $file->getFilename() => $file->getContents()
                        ]
                    ];
                }
            }

            foreach ($update as $key => $data) {
                $endpoint = '/downloads/files/' . $data['id'] . '/history';
                unset($data['time']);

                try {
                    $response = Beta::i()->post($data, $endpoint);
                }catch( ApiException $e) {
                    $errors[] = [
                        'file' => $data['title'],
                        'error' => $e->getMessage(),
                        'updated' => 1,
                    ];
                }
            }

            $endpoint = '/downloads/files';
            foreach ($new as $key => $data) {
                unset($data['time']);

                try {
                    $response = Beta::i()->post($data, $endpoint);
                }catch( ApiException $e) {
                    $errors[] = [
                        'file' => $data['title'],
                        'error' => $e->getMessage(),
                        'updated' => 0,
                    ];
                }
            }

            if (empty($errors) === false) {
                Log::log(json_encode($errors), 'Beta Uploader');
            }
        }
        return null;
    }

    /**
     * Cleanup
     *
     * If your task takes longer than 15 minutes to run, this method
     * will be called before execute(). Use it to clean up anything which
     * may not have been done
     *
     * @return    void
     */
    public function cleanup()
    {
    }
}
