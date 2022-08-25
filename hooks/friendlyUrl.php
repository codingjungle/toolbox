//<?php namespace toolbox_IPS_Http_Url_Friendly_aae2416467a1ba5699b5644f5b234e13e;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function implode;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class toolbox_hook_friendlyUrl extends _HOOK_CLASS_
{
    public static function buildFriendlyUrlComponentFromData(&$queryString, $seoTemplate, $seoTitles)
    {
        try {
            return parent::buildFriendlyUrlComponentFromData(
                $queryString,
                $seoTemplate,
                $seoTitles
            );
        } catch ( \IPS\Http\Url\Exception $e ) {
            throw new \IPS\Http\Url\Exception(
                $e->getMessage() . '. QueryString:' . $queryString .' SeoTemplate:' . $seoTemplate . ' SeoTitles:' . implode('&',$seoTitles),
                $e->getCode(),
                $e->getPrevious()
            );
        }
    }
}
