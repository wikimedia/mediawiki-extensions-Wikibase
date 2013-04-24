<?php

namespace Wikibase\Test;
use Exception;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Term;
use Wikibase\TermIndex;
use Wikibase\PropertyLabelResolver;
use Wikibase\TermPropertyLabelResolver;

/**
 * Test for TermPropertyLabelResolver
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
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermPropertyLabelResolverTest extends PropertyLabelResolverTest {

	/**
	 * @param string $lang
	 * @param Term[] $terms
	 *
	 * @return PropertyLabelResolver
	 */
	public function getResolver( $lang, $terms ) {
		$resolver = new TermPropertyLabelResolver(
			$lang,
			new MockTermIndexForPropertyLabelResolverTest( $terms ),
			new \HashBagOStuff()
		);

		return $resolver;
	}


	//NOTE: actual tests are inherited from PropertyLabelResolver

}

/**
 * Mock implementation of TermIndex.
 *
 * @note: this uses internal knowledge about which functions of TermIndex are used
 * by PropertyLabelResolver, and how.
 *
 * @todo: make a fully functional mock conforming to the contract of the TermIndex
 * interface and passing tests for that interface. Only then will TermPropertyLabelResolverTest
 * be a true blackbox test.
 *
 * @package Wikibase\Test
 */
class MockTermIndexForPropertyLabelResolverTest implements TermIndex {

	/**
	 * @var Term[]
	 */
	protected $terms;

	/**
	 * @param Term[] $terms
	 */
	public function __construct( $terms ) {
		$this->terms = $terms;
	}

	/**
	 * @throws Exception always
	 */
	public function getMatchingTermCombination( array $terms, $termType = null, $entityType = null, EntityId $excludeId = null ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $description = null, $entityType = null, $fuzzySearch = false ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function saveTermsOfEntity( Entity $entity ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function deleteTermsOfEntity( Entity $entity ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function getTermsOfEntity( EntityId $id ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function getTermsOfEntities( array $ids, $entityType, $language = null ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function termExists( $termValue, $termType = null, $termLanguage = null, $entityType = null ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * Implemented to fit the need of PropertyLabelResolver.
	 *
	 * @note: The $options parameters is ignored. The language to get is determined by the
	 * language of the first Term in $terms. $The termType and $entityType parameters are used,
	 * but the termType and entityType fields of the Terms in $terms are ignored.
	 *
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *
	 * @return Term[]
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() ) {
		$matchingTerms = array();

		$language = $terms[0]->getLanguage();

		foreach ( $this->terms as $term ) {
			if ( $term->getLanguage() === $language
				&& $term->getEntityType() === $entityType
				&& $term->getType() === $termType
			) {

				$matchingTerms[] = $term;
			}
		}

		return $matchingTerms;
	}

	/**
	 * @throws Exception always
	 */
	public function getMatchingIDs( array $terms, $entityType, array $options = array() ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function clear() {
		throw new Exception( 'not implemented by mock class ' );
	}
}