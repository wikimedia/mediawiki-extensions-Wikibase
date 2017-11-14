<?php

/**
 * @license GPL-2.0+
 */
return call_user_func( function() {
	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. 'wikibase-data-types' . DIRECTORY_SEPARATOR . 'src';

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', $dir, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	return [
		'dataTypes.__namespace' => $moduleTemplate + [
			'scripts' => [
				'dataTypes/__namespace.js'
			]
		],
		'dataTypes.DataType' => $moduleTemplate + [
			'scripts' => 'dataTypes/DataType.js',
			'dependencies' => 'dataTypes.__namespace',
		],
		'dataTypes.DataTypeStore' => [
			'scripts' => 'dataTypes/DataTypeStore.js',
			'dependencies' => [
				'dataTypes.__namespace',
				'dataTypes.DataType',
			]
		],
	];
} );
