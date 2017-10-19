<?php
/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'wikibase-data-values'
		. DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'valueFormatters';

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', $dir, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	return [
		'valueFormatters' => $moduleTemplate + [
				'scripts' => [
					'valueFormatters.js',
				],
			],

		'valueFormatters.ValueFormatter' => $moduleTemplate + [
				'scripts' => [
					'formatters/ValueFormatter.js',
				],
				'dependencies' => [
					'util.inherit',
					'valueFormatters',
				],
			],

		'valueFormatters.formatters' => $moduleTemplate + [
				'scripts' => [
					'formatters/NullFormatter.js',
					'formatters/StringFormatter.js',
				],
				'dependencies' => [
					'dataValues.values',
					'util.inherit',
					'valueFormatters.ValueFormatter',
				],
			],
	];
} );
