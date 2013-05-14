<?php

namespace Wikibase\Lib;

use Wikibase\Repo\WikibaseRepo;
use ValueParsers\ParserOptions;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimGuidValidator {

	protected $entityPrefixes;

	public function __construct( array $entityPrefixes ) {
		$this->entityPrefixes = $entityPrefixes;
	}

	/**
	 * Validates a claim guid
	 *
	 * @since 0.4
	 *
	 * @param string $guid
	 *
	 * @return boolean
	 */
	public function validate( $guid ) {
		if ( ! is_string( $guid ) ) {
			return false;
		}

		$keyParts = explode( '$', $guid );

		if ( count( $keyParts ) !== 2 ) {
			return false;
		}

		if ( ! $this->validateClaimGuidPrefix( $keyParts[0] ) || ! $this->validateGuid( $keyParts[1] ) ) {
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
		$guidFormat = '/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/';

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
		$options = new ParserOptions( array(
			EntityIdParser::OPT_PREFIX_MAP => $this->entityPrefixes
		) );

		$entityIdParser = new EntityIdParser( $options );
		$entityId = $entityIdParser->parse( $prefixedId );

		if ( ! ( $entityId instanceof \Wikibase\EntityId ) ) {
			wfDebugLog( __CLASS__, __METHOD__ . ': claim guid is missing an entity id prefix.' );
			return false;
		}

		return true;
	}

}
