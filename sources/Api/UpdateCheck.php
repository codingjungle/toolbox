<?php

/**
* @brief      UpdateCheck Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.1
* @version    -storm_version-
*/

namespace IPS\toolbox\Api;

use IPS\Settings;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
* UpdateCheck Class
* @mixin UpdateCheck
 * @mixin Oauth
*/
class _UpdateCheck extends Oauth
{
    protected static $instance;
    protected function setup(): void
    {
        $this->client = 'ccff05eb602312b6183ccaea8ed6a235';
        $this->secret = '642534793abd1831cc6e49d224e6bb03069b2c70c12543ae';
        $this->token = 'https://codingjungle.com/oauth/token/';
        $this->url = 'https://codingjungle.com/api/';
        $this->scopes = 'downloads';
        $this->accessToken = null;
    }

    protected function storeCredentials($credentials): mixed
    {
        Settings::i()->changeValues(['toolbox_at' => $credentials['access_token']]);
        return $credentials['access_token'];
    }

    protected function getCredentials(): ?string
    {
        return Settings::i()->toolbox_at;
    }

    protected function clearCredentials(): void
    {
        Settings::i()->changeValues(['toolbox_at' => null]);
    }
}
