<?php

return call_user_func( function() {
	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(
		'wikibase.client.getMwApiForRepo' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.client.getMwApiForRepo.js'
			),
			'dependencies' => array(
				'mw.config.values.wbRepo',
				'wikibase.api.getLocationAgnosticMwApi',
			)
		),
		'wikibase.client.init' => $moduleTemplate + array(
			'position' => 'top',
			'skinStyles' => array(
				'modern' => 'wikibase.client.css',
				'monobook' => 'wikibase.client.css',
				'vector' => array(
					'wikibase.client.css',
					'wikibase.client.vector.css'
				)
			),
		),
		'wikibase.client.nolanglinks' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => 'wikibase.client.nolanglinks.css',
		),
		'wikibase.client.currentSite' => $moduleTemplate + array(
			'class' => 'Wikibase\SiteModule'
		),
		'wikibase.client.page-move' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => 'wikibase.client.page-move.css'
		),
		'wikibase.client.changeslist.css' => $moduleTemplate + array(
			'styles' => 'wikibase.client.changeslist.css'
		),
		'wikibase.client.linkitem.init' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.client.linkitem.init.js'
			),
			'styles' => array(
				'wikibase.client.linkitem.init.css'
			),
			'messages' => array(
				'unknown-error'
			),
			'dependencies' => array(
				'jquery.spinner',
				'mediawiki.notify'
			),
		),
		'wikibase.client.PageConnector' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.client.PageConnector.js'
			),
			'dependencies' => array(
				'wikibase.sites'
			),
		),
		'jquery.wikibase.linkitem' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.linkitem.js'
			),
			'styles' => array(
				'jquery.wikibase/jquery.wikibase.linkitem.css'
			),
			'dependencies' => array(
				'jquery.spinner',
				'jquery.ui.dialog',
				'jquery.ui.suggester',
				'jquery.wikibase.siteselector',
				'jquery.wikibase.wbtooltip',
				'mediawiki.api',
				'mediawiki.util',
				'mediawiki.jqueryMsg',
				'wikibase.client.currentSite',
				'wikibase.sites',
				'wikibase.RepoApi',
				'wikibase.RepoApiError',
				'wikibase.client.PageConnector'
			),
			'messages' => array(
				'wikibase-linkitem-alreadylinked',
				'wikibase-linkitem-title',
				'wikibase-linkitem-linkpage',
				'wikibase-linkitem-selectlink',
				'wikibase-linkitem-input-site',
				'wikibase-linkitem-input-page',
				'wikibase-linkitem-confirmitem-text',
				'wikibase-linkitem-confirmitem-button',
				'wikibase-linkitem-success-link',
				'wikibase-linkitem-close',
				'wikibase-linkitem-not-loggedin-title',
				'wikibase-linkitem-not-loggedin',
				'wikibase-linkitem-failure',
				'wikibase-replicationnote',
				'wikibase-sitelinks-sitename-columnheading',
				'wikibase-sitelinks-link-columnheading'
			),
		)
	);

} );
