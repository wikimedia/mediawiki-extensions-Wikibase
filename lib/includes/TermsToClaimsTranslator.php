<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\Term;
use Wikibase\Claim;
use Wikibase\PropertyValueSnak;
use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;

/**
 * Can turn Term objects into Claims.
 *
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermsToClaimsTranslator {

	/**
	 * Term type => Property id
	 *
	 * @var int[]
	 */
	private $propertyIds = array();

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param int[] $propertyIds
	 */
	public function __construct( array $propertyIds ) {
		$this->propertyIds = $propertyIds;
	}

	/**
	 * Turns a set of terms representing the same property though in different languages into a Claim
	 * with a MultilingualTextValue in its main snak.
	 *
	 * @since 0.4
	 *
	 * @param Term[] $terms
	 *
	 * @return Claim
	 * @throws InvalidArgumentException
	 */
	public function termsToClaim( array $terms ) {
		if ( empty( $terms ) ) {
			throw new InvalidArgumentException( 'Need to have at least one term to construct a claim' );
		}

		$term = reset( $terms );
		$termType = $term->getType();

		$propertyId = $this->getPropertyIdForTermType( $termType );

		$monoTexts = array();

		foreach ( $terms as $term ) {
			if ( $term->getType() !== $termType ) {
				throw new InvalidArgumentException( 'Term types must be the same to construct a claim' );
			}

			$monoTexts[] = $this->termToMonoText( $term );
		}

		$multiText = new MultilingualTextValue( $monoTexts );

		$mainSnak = new PropertyValueSnak( $propertyId, $multiText );

		return new Claim( $mainSnak );
	}

	/**
	 * Turns a term into a Claim with MonolingualTextValue in its main snak.
	 *
	 * @since 0.4
	 *
	 * @param Term $term
	 *
	 * @return Claim
	 */
	public function termToClaim( Term $term ) {
		$propertyId = $this->getPropertyIdForTermType( $term->getType() );
		$value = $this->termToMonoText( $term );

		$mainSnak = new PropertyValueSnak( $propertyId, $value );

		return new Claim( $mainSnak );
	}

	/**
	 * Returns the property id for a term type.
	 *
	 * @since 0.4
	 *
	 * @param string $termType
	 *
	 * @return int
	 * @throws InvalidArgumentException
	 */
	private function getPropertyIdForTermType( $termType ) {
		if ( $termType === null ) {
			throw new InvalidArgumentException( 'Term type must be set to turn it into a claim' );
		}

		if ( !array_key_exists( $termType, $this->propertyIds ) ) {
			throw new InvalidArgumentException( 'Term type not mapped to a property id' );
		}

		return $this->propertyIds[$termType];
	}

	/**
	 * Returns a MonolingualTextValue constructed from the provided Term.
	 *
	 * @since 0.4
	 *
	 * @param Term $term
	 *
	 * @return MonolingualTextValue
	 * @throws InvalidArgumentException
	 */
	private function termToMonoText( Term $term ) {
		if ( $term->getLanguage() === null ) {
			throw new InvalidArgumentException( 'Term language needs to be set in order to turn it into a MonolingualTextValue' );
		}

		return new MonolingualTextValue( $term->getLanguage(), $term->getText() );
	}

}
