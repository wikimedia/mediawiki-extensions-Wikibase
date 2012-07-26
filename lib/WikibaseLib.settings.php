<?php

namespace Wikibase;


/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file Wikibase.hooks.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nikola Smolenski
 * @author Daniel Werner
 */
final class SettingsHooks {

	/**
	 * Adds default settings.
	 * Setting name (string) => setting value (mixed)
	 *
	 * @param array &$settings
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onWikibaseDefaultSettings( array &$settings ) {
		$settings = array_merge(
			$settings,
			array(
				// alternative: application/vnd.php.serialized
				'serializationFormat' => CONTENT_FORMAT_JSON,

				// Defaults to turn on deletion of empty items
				// set to true will always delete empty items
				'apiDeleteEmpty' => false,

				// Defaults to turn off use of keys
				// set to true will always return the key form
				'apiUseKeys' => true,

				// Set API in debug mode
				// do not turn on in production!
				'apiInDebug' => false,

				// Additional settings for API when debugging is on to
				// facilitate testing, do not turn on in production!
				'apiDebugWithWrite' => true,
				'apiDebugWithPost' => false,
				'apiDebugWithRights' => false,
				'apiDebugWithTokens' => false,

				// Which formats to use with keys when there are a "usekeys" in the URL
				// undefined entries are interpreted as false
				'formatsWithKeys' => array(
					'json' => true,
					'jsonfm' => true,
					'php' => false,
					'phpfm' => false,
					'wddx' => false,
					'wddxfm' => false,
					'xml' => false,
					'xmlfm' => false,
					'yaml' => true,
					'yamlfm' => true,
					'raw' => true,
					'rawfm' => true,
					'txtfm' => true,
					'dbg' => true,
					'dbgfm' => true,
					'dump' => true,
					'dumpfm' => true,
				),
				// settings for the user agent
				//TODO: This should REALLY be handled somehow as without it we could run into lots of trouble
				'clientTimeout' => 10, // this is before final timeout, without maxlag or maxage we can't hang around
				//'clientTimeout' => 120, // this is before final timeout, the maxlag value and then some
				'clientPageOpts' => array(
					'userAgent' => 'Wikibase',
				),
				'clientPageArgs' => array(
					'action' => 'query',
					'prop' => 'info',
					'redirects' => true,
					'converttitles' => true,
					'format' => 'json',
					//TODO: This should REALLY be handled somehow as without it we could run into lots of trouble
					//'maxage' => 5, // filter down repeated clicks, don't let clicky folks loose to fast
					//'smaxage' => 15, // give the proxy some time, don't let clicky folks loose to fast
					//'maxlag' => 100, // time to wait on a lagging server, hanging on for 100 sec is very aggressive
				),
			)
		);

		return true;
	}

}
