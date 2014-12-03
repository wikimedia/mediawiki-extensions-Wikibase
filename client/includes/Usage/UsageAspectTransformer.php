<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Transforms usage aspect based on a filter of aspects relevant in some context.
 * Relevant aspects for each entity are collected using the setRelevantAspects()
 * method.
 *
 * @example: If a page uses the "label" (L) and "title" (T) aspects of item Q1, a
 * UsageAspectTransformer that was set up to consider the label aspect of Q1
 * to be relevant will transform the usage Q1#L + Q1#T to the "relevant" usage Q1#L.
 *
 * @example: The "all" (X) aspect is treated specially: If a page uses the X aspect,
 * a  UsageAspectTransformer that was constructed to consider e.g. the label and title
 * aspects of Q1 to be relevant will transform the usage Q1#X to the "relevant"
 * usage Q1#L + Q1#T. Conversely, if a page uses the "sitelink" (S) aspect, a
 * UsageAspectTransformer that was constructed to consider all (X) usages relevant
 * will keep the usage Q1#S usage as "relevant".
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageAspectTransformer {

	/**
	 * @var array[] An associative array, mapping entity IDs to lists of aspect names.
	 */
	private $relevantAspectsPerEntity;

	/**
	 * @param EntityId $entityId
	 * @param string[] $aspects
	 */
	public function setRelevantAspects( EntityId $entityId, array $aspects ) {
		$key = $entityId->getSerialization();
		$this->relevantAspectsPerEntity[$key] = $aspects;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getRelevantAspects( EntityId $entityId ) {
		$key = $entityId->getSerialization();
		return isset( $this->relevantAspectsPerEntity[$key] ) ? $this->relevantAspectsPerEntity[$key] : array();
	}

	/**
	 * Gets EntityUsage objects for each aspect in $aspects that is relevant according to
	 * getRelevantAspects( $entityId ).
	 *
	 * @example: If was called with setRelevantAspects( $q3, array( 'T', 'L' ) ),
	 * getFilteredUsages( $q3, array( 'S', 'L' ) ) will return EntityUsage( $q3, 'L'),
	 * while getFilteredUsages( $q3, array( 'X' ) ) will return EntityUsage( $q3, 'T')
	 * and EntityUsage( $q3, 'L').
	 *
	 * @param EntityId $entityId
	 * @param string[] $aspects
	 *
	 * @return EntityUsage[]
	 */
	public function getFilteredUsages( EntityId $entityId, array $aspects ) {
		$relevant = $this->getRelevantAspects( $entityId );
		$effectiveAspects = $this->getFilteredAspects( $aspects, $relevant );

		return $this->buildEntityUsages( $entityId, $effectiveAspects );
	}

	/**
	 * Transforms the entity usages from $pageEntityUsages according to the relevant
	 * aspects defined by calling setRelevantAspects(). A new PageEntityUsages
	 * containing the filtered usage list is returned.
	 *
	 * @see getFilteredUsages()
	 *
	 * @param PageEntityUsages $pageEntityUsages
	 *
	 * @return PageEntityUsages
	 */
	public function transformPageEntityUsages( PageEntityUsages $pageEntityUsages ) {
		$entityIds = $pageEntityUsages->getEntityIds();
		$transformedPageEntityUsages = new PageEntityUsages( $pageEntityUsages->getPageId(), array() );

		foreach ( $entityIds as $id ) {
			$aspects = $pageEntityUsages->getUsageAspects( $id );
			$usages = $this->getFilteredUsages( $id, $aspects );
			$transformedPageEntityUsages->addUsages( $usages );
		}

		return $transformedPageEntityUsages;
	}

	/**
	 * @param EntityId $entityId
	 * @param string[] $aspects
	 *
	 * @return EntityUsage[]
	 */
	private function buildEntityUsages( EntityId $entityId, array $aspects ) {
		$usages = array();

		foreach ( $aspects as $aspect ) {
			$entityUsage = new EntityUsage( $entityId, $aspect );
			$key = $entityUsage->getIdentityString();

			$usages[$key] = $entityUsage;
		}

		ksort( $usages );
		return $usages;
	}

	/**
	 * Filter $aspects based on the aspects provided by $relevant, according to the rules
	 * defined for combining aspects (see class level documentation).
	 *
	 * @note This returns the intersection of $aspects and $relevant,
	 * except if on of the list contains the ALL_USAGE code (X).
	 * If X is present in $aspects, this method will return $relevant (if "all" is in the
	 * base set, the filtered set will be the filter itself).
	 * If X is present in $relevant, this method returns $aspects (if all aspects are relevant,
	 * nothing is filtered out).
	 *
	 * @param string[] $aspects
	 * @param string[] $relevant
	 *
	 * @return string[]
	 */
	private function getFilteredAspects( array $aspects, array $relevant ) {
		if ( empty( $aspects ) || empty( $relevant ) ) {
			return array();
		}

		if ( in_array( 'X', $aspects ) ) {
			return $relevant;
		} elseif ( in_array( 'X', $relevant ) ) {
			return $aspects;
		}

		return array_intersect( $aspects, $relevant );
	}

}
