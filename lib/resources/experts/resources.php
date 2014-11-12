<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	preg_match(
		'+' . preg_quote( DIRECTORY_SEPARATOR, '+' ) . '((?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR, '+' ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . DIRECTORY_SEPARATOR . $remoteExtPathParts[1],
	);

	return array(

		'wikibase.experts.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'__namespace.js'
			),
			'dependencies' => array(
				'wikibase',
			)
		),

		'wikibase.experts.getStore' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.experts.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.CommonsMediaType',
				'jquery.valueview.experts.GlobeCoordinateInput',
				'jquery.valueview.experts.MonolingualText',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.TimeInput',
				'jquery.valueview.experts.UnsupportedValue',
				'wikibase.datamodel.EntityId',
				'wikibase.experts.__namespace',
				'wikibase.experts.EntityIdInput',
			),
		),

		'wikibase.experts.EntityIdInput' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdInput.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.valueview.Expert',
				'jquery.valueview.experts.StringValue',
				'jquery.wikibase.entityselector',
				'mw.config.values.wbRepo',
				'mediawiki.util',
				'wikibase.experts.__namespace',
			),
		),
	);

} );
