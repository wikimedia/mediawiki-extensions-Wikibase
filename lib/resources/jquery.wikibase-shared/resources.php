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

	$modules = array(

		'jquery.wikibase.siteselector' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.siteselector.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.ui.ooMenu',
				'jquery.ui.suggester',
				'util.highlightSubstring',
			),
		),

		'jquery.wikibase.wbtooltip' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.wbtooltip.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.wbtooltip.css'
			),
			'dependencies' => array(
				'jquery.tipsy',
				'jquery.ui.toggler',
				'jquery.ui.widget',
			),
			'messages' => array(
				'wikibase-tooltip-error-details',
			),
		),

	);

	return $modules;

} );
