<?php

namespace Wikibase\Lib;

use Wikibase\Repo\WikibaseRepo;
use ValueParsers\ParserOptions;

/**
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimGuidValidator {

	/**
	 * Validates a claim guid
	 *
	 * @since 0.4
	 *
	 * @param string $guid
	 * @param null $validPrefix optional validation for a specific prefix
	 * @return boolean
	 */
	public function validate( $guid, $validPrefix = null ) {
		if ( ! $this->validateFormat( $guid ) ) {
			return false;
		}

		$guidParts = explode( '$', $guid );

		if( is_string( $validPrefix ) && strtoupper( $guidParts[0] ) !== strtoupper(  $validPrefix ) ){
			return false;
		}

		if ( ! $this->validateClaimGuidPrefix( $guidParts[0] ) || ! $this->validateGuid( $guidParts[1] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Basic validation for claim guid format
	 *
	 * @since 0.4
	 *
	 * @param string $guid
	 *
	 * @return boolean
	 */
	public function validateFormat( $guid ) {
		if ( ! is_string( $guid ) ) {
			return false;
		}

		$keyParts = explode( '$', $guid );

		if ( count( $keyParts ) !== 2 ) {
			wfDebugLog( __CLASS__, __METHOD__ . ': claim guid does not have the correct number of parts.' );
			return false;
		}

		return true;
	}

	/**
	 * Validate the second part of a claim guid, after the $
	 *
	 * @since 0.4
	 *
	 * @param string $guid
	 *
	 * @return boolean
	 */
	protected function validateGuid( $guid ) {
		$guidFormat = '/^\{?[A-Za-z0-9]{8}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{12}\}?$/';

		if ( ! ( preg_match( $guidFormat, $guid ) ) ) {
			wfDebugLog( __CLASS__, __METHOD__ . ': claim guid param has an invalid format.' );
			return false;
		}

		return true;
	}

	/**
	 * Validate the claim guid prefix is a valid entity id
	 *
	 * @since 0.4
	 *
	 * @param string $guid
	 *
	 * @return boolean
	 */
	protected function validateClaimGuidPrefix( $prefixedId ) {
		$options = new ParserOptions();
		$entityIdParser = new EntityIdParser( $options );
		$entityId = $entityIdParser->parse( $prefixedId );

		if ( ! ( $entityId instanceof \Wikibase\EntityId ) ) {
			wfDebugLog( __CLASS__, __METHOD__ . ': claim guid is missing an entity id prefix.' );
			return false;
		}

		return true;
	}



}
