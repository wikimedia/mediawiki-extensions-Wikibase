<?php
/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$mwVvResources = array(
		'mw.ext.dataValues' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.dataValues.js',
			),
			'dependencies' => array(
				// load all values. TODO: this is bad but the system is not as advanced as ValueView yet.
				'dataValues.values'
			),
			'messages' => array(
				'jan', 'january',
				'feb', 'february',
				'mar', 'march',
				'apr', 'april',
				'may', 'may_long',
				'jun', 'june',
				'jul', 'july',
				'aug', 'august',
				'sep', 'september',
				'oct', 'october',
				'nov', 'november',
				'dec', 'december',
			)
		),

	);

	// return "DataValue" module's native resources plus those required by the MW extension:
	return $mwVvResources + include( __DIR__ . '/resources.php' );
} );
