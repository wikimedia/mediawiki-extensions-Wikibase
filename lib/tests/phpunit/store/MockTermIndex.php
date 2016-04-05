<?php

namespace Wikibase\Test;

use Exception;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

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
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MockTermIndex implements TermIndex, LabelConflictFinder {

	/**
	 * @var TermIndexEntry[]
	 */
	protected $terms;

	/**
	 * @param TermIndexEntry[] $terms
	 */
	public function __construct( array $terms ) {
		$this->terms = $terms;
	}

	/**
	 * @see LabelConflictFinder::getLabelConflicts
	 *
	 * @param string $entityType The relevant entity type
	 * @param string[] $labels The label to look for
	 * @param array[]|null $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return EntityId[]
	 */
	public function getLabelConflicts( $entityType, array $labels, array $aliases = null ) {
		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string' );
		}

		if ( empty( $labels ) && empty( $aliases ) ) {
			return [];
		}

		$termTypes = ( $aliases === null )
			? array( TermIndexEntry::TYPE_LABEL )
			: array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS );

		$termTexts = ( $aliases === null )
			? $labels
			: array_merge( $labels, $aliases );

		$templates = $this->makeTemplateTerms( $termTexts, $termTypes );

		$conflicts = $this->getMatchingTerms(
			$templates,
			$termTypes,
			$entityType
		);

		return $conflicts;
	}

	/**
	 * @see LabelConflictFinder::getLabelWithDescriptionConflicts
	 *
	 * @param string $entityType The relevant entity type
	 * @param string[] $labels The label to look for
	 * @param string[] $descriptions The description to consider, if descriptions are relevant.
	 *
	 * @return EntityId[]
	 */
	public function getLabelWithDescriptionConflicts(
		$entityType,
		array $labels,
		array $descriptions
	) {
		$labels = array_intersect_key( $labels, $descriptions );
		$descriptions = array_intersect_key( $descriptions, $labels );

		if ( empty( $descriptions ) || empty( $labels ) ) {
			return [];
		}

		$labelConflicts = $this->getLabelConflicts(
			$entityType,
			$labels
		);

		if ( empty( $labelConflicts ) ) {
			return [];
		}

		$templates = $this->makeTemplateTerms( $descriptions, array( TermIndexEntry::TYPE_DESCRIPTION ) );

		$descriptionConflicts = $this->getMatchingTerms(
			$templates,
			TermIndexEntry::TYPE_DESCRIPTION,
			$entityType
		);

		$conflicts = $this->intersectConflicts( $labelConflicts, $descriptionConflicts );

		return $conflicts;
	}

	/**
	 * @param array[]|string[] $textsByLanguage A list of texts, or a list of lists of texts (keyed by language on the top level)
	 * @param string[] $types
	 *
	 * @return TermIndexEntry[]
	 */
	private function makeTemplateTerms( array $textsByLanguage, array $types ) {
		$terms = [];

		foreach ( $textsByLanguage as $lang => $texts ) {
			$texts = (array)$texts;

			foreach ( $texts as $text ) {
				foreach ( $types as $type ) {
					$terms[] = new TermIndexEntry( array(
						'termText' => $text,
						'termLanguage' => $lang,
						'termType' => $type,
					) );
				}
			}
		}

		return $terms;
	}

	/**
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $entityType
	 * @param bool $fuzzySearch
	 *
	 * @return EntityId[]
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $entityType = null,
		$fuzzySearch = false
	) {
		$entityIds = [];

		foreach ( $this->terms as $term ) {
			if ( $languageCode !== null && $term->getLanguage() !== $languageCode ) {
				continue;
			}

			if ( $entityType !== null && $term->getEntityType() !== $entityType ) {
				continue;
			}

			if ( $term->getType() !== 'label' ) {
				continue;
			}

			if ( !$fuzzySearch ) {
				if ( $term->getText() === $label ) {
					$entityIds[] = $term->getEntityId();
				}
			} else {
				if ( strpos( $term->getText(), $label ) !== false ) {
					$entityIds[] = $term->getEntityId();
				}
			}
		}

		return $entityIds;
	}

	/**
	 * @throws Exception always
	 */
	public function saveTermsOfEntity( EntityDocument $entity ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @param EntityId $entityId
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @return TermIndexEntry[]
	 */
	public function getTermsOfEntity(
		EntityId $entityId,
		array $termTypes = null,
		array $languageCodes = null
	) {
		$matchingTerms = [];

		if ( is_array( $termTypes ) ) {
			$termTypes = array_flip( $termTypes );
		}

		if ( is_array( $languageCodes ) ) {
			$languageCodes = array_flip( $languageCodes );
		}

		foreach ( $this->terms as $term ) {
			if ( ( is_array( $termTypes ) && !isset( $termTypes[$term->getType()] ) )
				|| ( is_array( $languageCodes ) && !isset( $languageCodes[$term->getLanguage()] ) )
				|| !$entityId->equals( $term->getEntityId() )
			) {
				continue;
			}

			$matchingTerms[] = $term;
		}

		return $matchingTerms;
	}

	/**
	 * @see TermIndex::getTermsOfEntities
	 *
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @return TermIndexEntry[]
	 */
	public function getTermsOfEntities(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	) {
		$terms = [];

		foreach ( $entityIds as $id ) {
			$terms = array_merge(
				$terms,
				$this->getTermsOfEntity( $id, $termTypes, $languageCodes )
			);
		}

		return $terms;
	}

	/**
	 * @throws Exception always
	 */
	public function termExists(
		$termValue,
		$termType = null,
		$termLanguage = null,
		$entityType = null
	) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * Implemented to fit the need of PropertyLabelResolver.
	 *
	 * @note: The $options parameters is ignored. The language to get is determined by the
	 * language of the first Term in $terms. $The termType and $entityType parameters are used,
	 * but the termType and entityType fields of the Terms in $terms are ignored.
	 *
	 * @param TermIndexEntry[] $terms
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return TermIndexEntry[]
	 */
	public function getMatchingTerms(
		array $terms,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		$matchingTerms = [];

		$termType = $termType === null ? null : (array)$termType;
		$entityType = $entityType === null ? null : (array)$entityType;

		foreach ( $this->terms as $term ) {
			if ( ( $entityType === null || in_array( $term->getEntityType(), $entityType ) )
				&& ( $termType === null || in_array( $term->getType(), $termType ) )
				&& $this->termMatchesTemplates( $term, $terms, $options )
			) {
				$matchingTerms[] = $term;
			}
		}

		$limit = isset( $options['LIMIT'] ) ? $options['LIMIT'] : 0;

		if ( $limit > 0 ) {
			$matchingTerms = array_slice( $matchingTerms, 0, $limit );
		}

		return $matchingTerms;
	}

	/**
	 * Returns the same as getMatchingTerms simply making sure only one term
	 * is returned per EntityId. This is the first term.
	 * Weighting does not affect the order of return by this method.
	 *
	 * @param TermIndexEntry[] $terms
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return TermIndexEntry[]
	 */
	public function getTopMatchingTerms(
		array $terms,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		$options['orderByWeight'] = true;
		$terms = $this->getMatchingTerms( $terms, $termType, $entityType, $options );
		$previousEntityIdSerializations = [];
		$returnTerms = [];
		foreach ( $terms as $termIndexEntry ) {
			if ( !in_array( $termIndexEntry->getEntityId()->getSerialization(), $previousEntityIdSerializations ) ) {
				$returnTerms[] = $termIndexEntry;
				$previousEntityIdSerializations[] = $termIndexEntry->getEntityId()->getSerialization();
			}
		}
		return $returnTerms;
	}

	/**
	 * @throws Exception always
	 */
	public function clear() {
		$this->terms = [];
	}

	/**
	 * Rekeys a list of Terms based on EntityId and language.
	 *
	 * @param TermIndexEntry[] $conflicts
	 *
	 * @return TermIndexEntry[]
	 */
	private function rekeyConflicts( array $conflicts ) {
		$rekeyed = [];

		foreach ( $conflicts as $term ) {
			$key = $term->getEntityId()->getSerialization();
			$key .= '/' . $term->getLanguage();

			$rekeyed[$key] = $term;
		}

		return $rekeyed;
	}

	/**
	 * Intersects two lists of Terms based on EntityId and language.
	 *
	 * @param TermIndexEntry[] $base
	 * @param TermIndexEntry[] $filter
	 *
	 * @return TermIndexEntry[]
	 */
	private function intersectConflicts( array $base, array $filter ) {
		$base = $this->rekeyConflicts( $base );
		$filter = $this->rekeyConflicts( $filter );

		return array_intersect_key( $base, $filter );
	}

	/**
	 * @param TermIndexEntry $term
	 * @param TermIndexEntry[] $templates
	 * @param array $options
	 *
	 * @return bool
	 */
	private function termMatchesTemplates( TermIndexEntry $term, array $templates, array $options = [] ) {
		foreach ( $templates as $template ) {
			if ( $template->getType() !== null && $template->getType() != $term->getType() ) {
				continue;
			}

			if ( $template->getEntityType() !== null && $template->getEntityType() != $term->getEntityType() ) {
				continue;
			}

			if ( $template->getLanguage() !== null && $template->getLanguage() != $term->getLanguage() ) {
				continue;
			}

			if ( $template->getText() !== null && !$this->textMatches( $template->getText(), $term->getText(), $options ) ) {
				continue;
			}

			if ( $template->getEntityId() !== null && !$template->getEntityId()->equals( $term->getEntityType() ) ) {
				continue;
			}

			return true;
		}

		return false;
	}

	private function textMatches( $find, $text, array $options = [] ) {

		if ( isset( $options[ 'caseSensitive' ] ) && !$options[ 'caseSensitive' ] ) {
			$find = strtolower( $find );
			$text = strtolower( $text );
		}

		if ( isset( $options[ 'prefixSearch' ] ) && $options[ 'prefixSearch' ] ) {
			$text = substr( $text, 0, strlen( $find ) );
		}

		return $find === $text;
	}

}
