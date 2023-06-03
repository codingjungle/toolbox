<?php

/**
* @brief      Beta Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.1
* @version    -storm_version-
*/

namespace IPS\toolbox\Api;

use IPS\Settings;

use const DT_BETA_URL;
use const DT_BETA_CLIENT_ID;
use const DT_BETA_CLIENT_SECRET;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
* Beta Class
* @mixin Beta
*/
class _Beta1 extends Oauth
{
    protected static $instance;

    protected function setup(): void
    {
        $this->client = \defined('DT_BETA_CLIENT_ID') ? DT_BETA_CLIENT_ID : '';
        $this->secret = \defined('DT_BETA_CLIENT_SECRET') ? DT_BETA_CLIENT_SECRET : '';
        $this->token = \defined('DT_BETA_URL') ? DT_BETA_URL . 'oauth/token/' : '';
        $this->url = \defined('DT_BETA_URL') ? DT_BETA_URL . 'api/' : '';
        $this->scopes = 'profile';
    }

    protected function storeCredentials($credentials): string
    {
        Settings::i()->changeValues(['toolbox_beta_key' => $credentials['access_token']]);
        return $credentials['access_token'];
    }

    protected function getCredentials(): ?string
    {
        return empty(Settings::i()->toolbox_beta_key) ? Settings::i()->toolbox_beta_key : null;
    }

    protected function clearCredentials(): void
    {
        Settings::i()->changeValues(['toolbox_beta_key' => null]);
    }
}
