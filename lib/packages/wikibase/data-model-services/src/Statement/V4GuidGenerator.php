<?php

namespace Wikibase\DataModel\Services\Statement;

/**
 * Globally Unique Identifier generator.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class V4GuidGenerator {

	/**
	 * Generates and returns a GUID.
	 * @see http://php.net/manual/en/function.com-create-guid.php
	 * @see GuidGenerator::newGuid
	 *
	 * @since 1.0
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
