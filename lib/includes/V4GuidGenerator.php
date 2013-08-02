<?php

namespace Wikibase\Lib;

/**
 * Globally Unique IDentifier generator.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class V4GuidGenerator implements GuidGenerator {

	/**
	 * Generates and returns a GUID.
	 * @see http://php.net/manual/en/function.com-create-guid.php
	 * @see GuidGenerator::newGuid
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function newGuid() {
		if ( function_exists( 'com_create_guid' ) ) {
			return trim( com_create_guid(), '{}' );
		}

		return sprintf(
			'%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 16384, 20479 ),
			mt_rand( 32768, 49151 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 )
		);
	}

}
