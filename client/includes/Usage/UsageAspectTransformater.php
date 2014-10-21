<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Transforms usage aspect based on a filter of aspects relevant in some context.
 *
 * @example: If a page uses the "label" (L) and "title" (T) aspects of item Q1, a
 * UsageAspectTransformer that was constructed to consider the label aspect of Q1
 * to be relevant will transform the usage Q1#L+T to the "relevant" usage Q1#L.
 *
 * @example: The "all" (X) aspect is treated specially: If a page uses the X aspect,
 * a  UsageAspectTransformer that was constructed to consider e.g. the label and title
 * aspects of Q1 to be relevant will transform the usage Q1#X to the "relevant" usage Q1#L+T.
 * Conversely, if a page uses the "sitelink" (S) aspect, a  UsageAspectTransformer that was
 * constructed to consider all (X) usages relevant will keep the usage Q1#S usage as "relevant".
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
	 * @param array[] $aspects
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
	 * Gets the effective usages for the given aspects, based on the knowledge
	 * about relevant aspects for each EntityId, as provided to setRelevantAspects.
	 *
	 * @param EntityId $entityId
	 * @param string[] $aspects
	 *
	 * @return EntityUsage[] $usages;
	 */
	public function getEffectiveUsages( EntityId $entityId, array $aspects ) {
		$relevant = $this->getRelevantAspects( $entityId );
		$effectiveAspects = $this->getEffectiveAspects( $aspects, $relevant );

		return $this->buildEntityUsages( $entityId, $effectiveAspects );
	}

	/**
	 * Transforms the entity usages for the given page according to the knowledge
	 * about relevant aspects for each EntityId, as provided to setRelevantAspects.
	 * A new PageEntityUsages containing the transformed usage list is returned.
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
			$usages = $this->getEffectiveUsages( $id, $aspects );
			$transformedPageEntityUsages->addUsages( $usages );
		}

		return $transformedPageEntityUsages;
	}

	/**
	 * @param EntityId $entityId
	 * @param array[] $aspects
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
	 * Combines the list of used aspects with a list of relevant aspects
	 * to get a list of effective aspects.
	 *
	 * @note This returns the intersection of the two lists of aspects,
	 * except if on of the list contains the ALL_USAGE code (X). If X
	 * is in $used, this method will return $relevant (if all are used,
	 * all relevant aspects are effective). If X is present in $relevant,
	 * this method returns $used (if all aspects are relevant, the ones
	 * actually used are the effective ones).
	 *
	 * @param string[] $used
	 * @param string[] $relevant
	 *
	 * @return array|string[]
	 */
	private function getEffectiveAspects( array $used, array $relevant ) {
		if ( empty( $used ) || empty( $relevant ) ) {
			return array();
		}

		if ( in_array( 'X', $used ) ) {
			return $relevant;
		} elseif ( in_array( 'X', $relevant ) ) {
			return $used;
		}

		return array_intersect( $used, $relevant );
	}

}
 