//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    exit;
}

class toolbox_hook_themeAdminGlobal extends _HOOK_CLASS_
{

    /* !Hook Data - DO NOT REMOVE */
    public static function hookData()
    {
        if (\is_callable('parent::hookData')) {
            return array_merge_recursive(
                array(
                    'tabs' =>
                        array(
                            0 =>
                                array(
                                    'selector' => 'div.acpBlock',
                                    'type'     => 'replace',
                                    'content'  => '
{{if \IPS\Request::i()->controller === \'developer\' && !\IPS\Request::i()->isAjax()}}
<div class="ipsColumns">
	<div class="ipsColumn ipsColumn_wide">
  	{{$sideBar = \IPS\toolbox\Application::getSidebar();}}
      {$sideBar|raw}
  	</div>
	<div class="ipsColumn ipsColumn_fluid">
	{{endif}}
		<div class=\'acpBlock\'>
			<div class=\'ipsTabs {$tabClasses} ipsClearfix acpFormTabBar\' id=\'elTabs_{expression="md5( $url->acpQueryString() )"}\' data-ipsTabBar data-ipsTabBar-contentArea=\'#ipsTabs_content_{expression="md5( $url->acpQueryString() )"}\' {{if \IPS\Request::i()->isAjax()}}data-ipsTabBar-updateURL=\'false\'{{endif}}>
				<a href=\'#elTabs_{expression="md5( $url->acpQueryString() )"}\' data-action=\'expandTabs\'><i class=\'fa fa-caret-down\'></i></a>
				<ul role=\'tablist\'>
					{{foreach $tabNames as $i => $name}}
					<li>
						<a href=\'{{if $i}}{$url->setQueryString( $tabParam, $i )}{{else}}{$url}{{endif}}\' id=\'{expression="md5( $url->acpQueryString() )"}_tab_{$i}\' class="ipsTabs_item {{if $i == $activeId}}ipsTabs_activeItem{{endif}}" title=\'{lang="{$name}"}\' role="tab" aria-selected="{{if $i == $activeId}}true{{else}}false{{endif}}">
							{lang="$name"}
							{{if \IPS\IN_DEV}}{template="searchKeywords" app="core" group="global" params="$url->setQueryString( \'tab\', $i )->acpQueryString(), $name"}{{endif}}
						</a>
					</li>
					{{endforeach}}
				</ul>
			</div>
			<section id=\'ipsTabs_content_{expression="md5( $url->acpQueryString() )"}\' class=\'acpFormTabContent\'>
				{{foreach $tabNames as $i => $name}}
				{{if $i == $activeId}}
				<div id=\'ipsTabs_elTabs_{expression="md5( $url->acpQueryString() )"}_{expression="md5( $url->acpQueryString() )"}_tab_{$i}_panel\' class="ipsTabs_panel {$panelClasses}" aria-labelledby="{expression="md5( $url->acpQueryString() )"}_tab_{$i}" aria-hidden="false">
					{$defaultContent|raw}
				</div>
				{{endif}}
				{{endforeach}}
			</section>
		</div>
		{{if \IPS\Request::i()->do === \'editSchema\'}}
	</div>
</div>
{{endif}}
',
                                ),
                        ),
                ),
                parent::hookData()
            );
        }
        return [];
    }
    /* End Hook Data */


}
