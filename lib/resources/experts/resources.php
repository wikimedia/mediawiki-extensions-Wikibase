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
				'wikibase.experts.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.valueview.ExpertFactory',
				'jquery.valueview.experts.CommonsMediaType',
				'jquery.valueview.experts.GlobeCoordinateValue',
				'jquery.valueview.experts.QuantityType',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.TimeValue',
				'jquery.valueview.experts.UnsupportedValue',
				'jquery.valueview.experts.UrlType',
				'wikibase',
				'wikibase.dataTypes',
				'wikibase.datamodel',
				'wikibase.experts.EntityIdInput',
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
	);

} );
