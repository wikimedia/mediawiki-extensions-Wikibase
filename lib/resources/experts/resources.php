<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(

		'wikibase.experts' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.experts.register.js',
			),
			'dependencies' => array(
				'dataTypes',
				'jquery.valueview.experts.CommonsMediaType',
				'jquery.valueview.experts.UrlType',
				'mw.ext.valueView',
				'wikibase.dataTypes',
				'wikibase.datamodel',
				'wikibase.experts.EntityIdValue',
			),
		),

		'wikibase.experts.EntityIdInput' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdInput.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.event.special.eachchange',
				'jquery.valueview.Expert',
				'jquery.valueview.experts.StringValue',
				'jquery.wikibase.entityselector',
				'mediawiki.util',
				'wikibase',
				'wikibase.datamodel',
			),
		),

		'wikibase.experts.EntityIdValue' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdValue.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.valueview.Expert',
				'jquery.valueview.BifidExpert',
				'jquery.valueview.experts.StaticDom',
				'wikibase',
				'wikibase.experts.EntityIdInput',
			),
		),

	);

} );
