<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use SiteList;

/**
 * TODO: Class desc
 *
 * @license GPL-2.0+
 */
class SiteLinkChangeOpSerializationValidator {

	/**
	 * Check some of the supplied data for sitelink arg
	 *
	 * @param array $arg The argument array to verify // TODO: rename
	 * @param string $siteCode The site code used in the argument
	 * @param SiteList|null $sites The valid sites.
	 *
	 * @throws ChangeOpDeserializationException
	 */
	public function checkSiteLinks( $arg, $siteCode, SiteList &$sites = null ) {
		$this->assertArray( $arg, 'An array was expected, but not found' );
		$this->assertString( $arg['site'], 'A string was expected, but not found' );

		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $arg['site'] ) {
				throw new ChangeOpDeserializationException( "inconsistent site: $siteCode is not equal to {$arg['site']}", 'inconsistent-site' );
			}
		}

		if ( $sites !== null && !$sites->hasSite( $arg['site'] ) ) {
			throw new ChangeOpDeserializationException( 'Unknown site: ' . $arg['site'], 'not-recognized-site' );
		}

		if ( isset( $arg['title'] ) ) {
			$this->assertString( $arg['title'], 'A string was expected, but not found' );
		}

		if ( isset( $arg['badges'] ) ) {
			$this->assertArray( $arg['badges'], 'Badges: an array was expected, but not found' );
			foreach ( $arg['badges'] as $badge ) {
				$this->assertString( $badge, 'Badges: a string was expected, but not found' );
			}
		}
	}

	/**
	 * @param string $message
	 * @param string $errorCode
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function throwException( $message, $errorCode ) {
		throw new ChangeOpDeserializationException( $message, $errorCode );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertArray( $value, $message ) {
		$this->assertType( 'array', $value, $message );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertString( $value, $message ) {
		$this->assertType( 'string', $value, $message );
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertType( $type, $value, $message ) {
		if ( gettype( $value ) !== $type ) {
			$this->throwException( $message, 'not-recognized-' . $type );
		}
	}

}