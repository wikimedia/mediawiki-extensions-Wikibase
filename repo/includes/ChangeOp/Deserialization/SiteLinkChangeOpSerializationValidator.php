<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use SiteList;

/**
 * Validates the structure of the site link change request.
 *
 * @license GPL-2.0+
 */
class SiteLinkChangeOpSerializationValidator {

	/**
	 * @var SiteLinkBadgeChangeOpSerializationValidator
	 */
	private $badgeSerializationValidator;

	public function __construct( SiteLinkBadgeChangeOpSerializationValidator $badgeChangeOpSerializationValidator ) {
		$this->badgeSerializationValidator = $badgeChangeOpSerializationValidator;
	}

	/**
	 * @param array $serialization Site link serialization array
	 * @param string $siteCode
	 * @param SiteList|null $sites Valid sites. Null for skipping site validity check.
	 *
	 * @throws ChangeOpDeserializationException
	 */
	public function validateSiteLinkSerialization( $serialization, $siteCode, SiteList &$sites = null ) {
		$this->assertArray( $serialization, 'An array was expected, but not found' );

		if ( !array_key_exists( 'site', $serialization ) ) {
			throw new ChangeOpDeserializationException( 'Site must be provided', 'no-site' );
		}
		$this->assertString( $serialization['site'], 'A string was expected, but not found' );

		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $serialization['site'] ) {
				throw new ChangeOpDeserializationException(
					"inconsistent site: $siteCode is not equal to {$serialization['site']}",
					'inconsistent-site'
				);
			}
		}

		if ( $sites !== null && !$sites->hasSite( $serialization['site'] ) ) {
			throw new ChangeOpDeserializationException( 'Unknown site: ' . $serialization['site'], 'not-recognized-site' );
		}

		if ( isset( $serialization['title'] ) ) {
			$this->assertString( $serialization['title'], 'A string was expected, but not found' );
		}

		if ( isset( $serialization['badges'] ) ) {
			$this->assertArray( $serialization['badges'], 'Badges: an array was expected, but not found' );
			$this->badgeSerializationValidator->validateBadgeSerialization( $serialization['badges'] );
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
