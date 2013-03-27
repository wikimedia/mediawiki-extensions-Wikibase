<?php

namespace Wikibase\Test;
use Wikibase\TermIndex;
use Wikibase\Term;
use Wikibase\Entity;
use Wikibase\EntityId;

/**
 * Mock implementation of TermIndex
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockTermIndex implements TermIndex {

	/**
	 * @var Term[]
	 */
	protected $terms = array();

	/**
	 * @param Term[] $terms
	 */
	public function __construct( array $terms = array() ) {
		$this->terms = $terms;
	}

	/**
	 * Returns true if the concrete Term $term matches the query pattern given by $qterm,
	 * according to $options
	 *
	 * @param \Wikibase\Term $term
	 * @param \Wikibase\Term $qterm
	 * @param array          $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *
	 * @return bool true if the terms match.
	 */
	public function match( Term $term, Term $qterm, $options = array() ) {
		if ( $qterm->getType() !== null && $term->getType() !== $qterm->getType() ) {
			return false;
		}

		if ( $qterm->getLanguage() !== null && $term->getLanguage() !== $qterm->getLanguage() ) {
			return false;
		}

		if ( $qterm->getEntityType() !== null && $term->getEntityType() !== $qterm->getEntityType() ) {
			return false;
		}

		if ( $qterm->getEntityId() !== null && $term->getEntityId() !== $qterm->getEntityId() ) {
			return false;
		}

		if ( $qterm->getText() !== null && $term->getText() !== $qterm->getText() ) {
			if ( $term->getText() === null ) {
				return false;
			}

			if ( isset( $options['caseSensitive'] ) && !$options['caseSensitive'] ) {
				$t = $term->getNormalizedText();
				$q = $qterm->getNormalizedText();
			} else {
				$t = $term->getText();
				$q = $qterm->getText();
			}

			if ( isset( $options['prefixSearch'] ) && $options['prefixSearch'] ) {
				if ( strpos( $t, $q ) !== 0 ) {
					return false;
				}
			} else {
				if ( $t !== $q ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Returns the type, id tuples for the entities with the provided label in the specified language.
	 *
	 * @since 0.1
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $description
	 * @param string|null $entityType
	 * @param bool $fuzzySearch if false, only exact matches are returned, otherwise more relaxed search . Defaults to false.
	 *
	 * @return array of array( entity type, entity id )
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $description = null, $entityType = null, $fuzzySearch = false ) {
		$options = array();

		if ( $fuzzySearch ) {
			$options['caseSensitive'] = false;
			$options['prefixSearch'] = true;
		}

		$qterm = new Term(
			array(
				'termText' => $label,
				'termLanguage' => $languageCode,
				'termType' => Term::TYPE_LABEL
			)
		);

		$ids = $this->getMatchingIDs( array( $qterm ), $entityType, $options );

		// description given: search again & intersect results.
		if ( $description !== null ) {
			$qterm = new Term(
				array(
					'termText' => $description,
					'termLanguage' => $languageCode,
					'termType' => Term::TYPE_DESCRIPTION
				)
			);

			$descriptionIds = $this->getMatchingIDs( array( $qterm ), $entityType, $options );

			$ids = array_intersect_key( $ids, $descriptionIds );
		}

		$ids = array_map(
			function ( EntityId $id )  {
				return array( $id->getEntityType(), $id->getNumericId() );
			},
			$ids
		);

		return $ids;
	}

	/**
	 * Saves the terms of the provided entity in the term cache.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( Entity $entity ) {
		$this->deleteTermsOfEntity( $entity );
		$newTerms = $entity->getTerms();

		foreach ( $newTerms as $term ) {
			$this->terms[] = $term;
		}

		return true;
	}

	/**
	 * Deletes the terms of the provided entity from the term cache.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( Entity $entity ) {
		$c = 0;

		foreach ( $this->terms as $k => $term ) {
			if ( $term->getEntityType() === $entity->getType()
				&& $term->getEntityId() === $entity->getId()->getNumericId() ) {

				unset( $this->terms[$k] );
				$c++;
			}
		}

		return ( $c > 0 );
	}

	/**
	 * Returns the terms stored for the given entity.
	 *
	 * @param EntityId $id
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntity( EntityId $id ) {
		return array_filter( $this->terms,
			function( Term $term ) use ( $id ) {
				return ( $term->getEntityType() === $id->getEntityType()
					&& $term->getEntityId() === $id->getNumericId() );
			}
		);
	}

	/**
	 * Returns the terms stored for the given entities. Can be filtered by language.
	 * Note that the entities must all be of the given type.
	 *
	 * @since 0.4
	 *
	 * @param EntityId[] $ids
	 * @param string $entityType
	 * @param string|null $language language code
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntities( array $ids, $entityType, $language = null ) {
		$ids = array_map(
			function ( EntityId $id ) {
				return $id->getNumericId();
			},
			$ids
		);

		$that = $this; // muhahaha!

		$terms = array_filter( $this->terms,
			function( Term $term ) use ( $that, $ids, $entityType, $language ) {

				$qterm = new Term(
					array(
						'termLanguage' => $language,
						'entityType' => $entityType,
					)
				);

				return in_array( $term->getEntityId(), $ids )
					&& $that->match( $term, $qterm );
			}
		);

		return $terms;
	}

	/**
	 * Returns if a term with the specified parameters exists.
	 *
	 * @since 0.1
	 *
	 * @param string $termValue
	 * @param string|null $termType
	 * @param string|null $termLanguage Language code
	 * @param string|null $entityType
	 *
	 * @return boolean
	 */
	public function termExists( $termValue, $termType = null, $termLanguage = null, $entityType = null ) {
		/* @var Term $term */
		foreach ( $this->terms as $term ) {

			$qterm = new Term(
				array(
					'termText' => $termValue,
					'termLanguage' => $termLanguage,
					'termType' => $termType,
					'entityType' => $entityType,
				)
			);

			if ( $this->match( $term, $qterm ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the terms that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A default can be provided for termType and entityType via the corresponding
	 * method parameters.
	 *
	 * The return value is an array of Terms where entityId, entityType,
	 * termType, termLanguage, termText are all set.
	 *
	 * @since 0.2
	 *
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return array
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() ) {
		return $this->getMatchesInternal( false, $terms, $termType, $entityType, $options );
	}

	/**
	 * Returns the terms that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by AND if $conjunction is true,
	 * by OR otherwise. The fields of the terms are joined by AND.
	 *
	 * A default can be provided for termType and entityType via the corresponding
	 * method parameters.
	 *
	 * The return value is an array of Terms where entityId, entityType,
	 * termType, termLanguage, termText are all set.
	 *
	 * @since 0.2
	 *
	 * @param bool   $conjunction if true, all terms must be matched;
	 *        if false, any of the terms must be matched.
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return array
	 */
	protected function getMatchesInternal( $conjunction, array $terms, $termType = null, $entityType = null, array $options = array() ) {
		$result = array();

		/* @var Term $term */
		foreach ( $this->terms as $term ) {
			$matched = 0;

			foreach ( $terms as $qterm ) {
				$qterm = new Term(
					$qterm->getFields()
				);

				if ( $qterm->getType() === null && $termType !== null ) {
					$qterm->setType( $termType );
				}

				if ( $qterm->getEntityType() === null && $entityType !== null ) {
					$qterm->getEntityType( $entityType );
				}

				if ( $this->match( $term, $qterm, $options ) ) {
					$matched++;

					if ( !$conjunction ) {
						// any of the terms must match
						break;
					}
				}
			}

			if ( $matched === count( $terms ) || ( !$conjunction && $matched > 0 ) ) {
				$result[] = $term;

				if ( isset( $options['LIMIT'] ) && count( $result ) >= $options['LIMIT'] ) {
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the IDs that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A single entityType has to be provided.
	 *
	 * @since 0.4
	 *
	 * @param Term[] $terms
	 * @param string $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return EntityId[]
	 */
	public function getMatchingIDs( array $terms, $entityType, array $options = array() ) {
		$terms = $this->getMatchingTerms( $terms, null, $entityType, array_diff_key( $options, array( 'LIMIT' ) ) );
		$ids = array();

		/* @var Term $term */
		foreach ( $terms as $term ) {
			$id = new EntityId( $term->getEntityType(), $term->getEntityId() );
			$ids[ $id->getPrefixedId() ] = $id;

			if ( isset( $options['LIMIT'] ) && count( $ids ) >= $options['LIMIT'] ) {
				break;
			}
		}

		return $ids;
	}


	/**
	 * Clears all terms from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		$this->terms = array();
		return true;
	}

	/**
	 * Takes an array in which each element in array of of Term.
	 * These terms can be incomplete so the search is not restrained on some fields.
	 *
	 * Looks for terms of a single entity that has a matching term for each element in one of the array of Term.
	 * If a match is found, the terms for that entity are returned complete with entity id and entity type info.
	 * The result is thus either an empty array when no match is found or an array with Term elements of size
	 * equal to the provided array of Term that matched.
	 *
	 * $termType and $entityType can be provided as default constraints for terms not having these fields set.
	 *
	 * $excludeId can be used to exclude any terms for the entity that matches this info.
	 *
	 * @since    0.4
	 *
	 * @param $Term[][]     $termSets
	 * @param string|null   $termType
	 * @param string|null   $entityType
	 * @param EntityId|null $excludeId
	 *
	 * @return Term[]
	 */
	public function getMatchingTermCombination( array $termSets, $termType = null, $entityType = null, EntityId $excludeId = null ) {
		$result = array();

		/* @var Term $term */
		foreach ( $termSets as $termSet ) {
			$matches = $this->getMatchesInternal( true, $termSet, $termType, $entityType );
			$result = array_merge( $result, $matches );
		}

		if ( $excludeId ) {
			$result = array_filter(
				$result,
				function ( Term $term ) use ( $excludeId ) {
					return $term->getEntityType() !== $excludeId->getEntityType()
						|| $term->getEntityId() !== $excludeId->getNumericId();
				}
			);
		}

		return $result;
	}
}
