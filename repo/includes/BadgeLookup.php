<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityInfoBuilder;

/**
 * Looks up badges and their titles.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BadgeLookup {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var array
	 */
	private $badgeItems;

	/**
	 * @var EntityInfoBuilder
	 */
	private $entityInfoBuilder;

	/**
	 * @var string[]|null
	 */
	private $badgeTitles = null;

	public function __construct( $languageCode, array $badgeItems, EntityInfoBuilder $entityInfoBuilder ) {
		$this->languageCode = $languageCode;
		$this->badgeItems = $badgeItems;
		$this->entityInfoBuilder = $entityInfoBuilder;
	}

	/**
	 * Returns the titles for all available badges.
	 *
	 * @since 0.5
	 *
	 * @return string[] array mapping badge ids to titles
	 */
	public function getBadgeTitles() {
		if ( $this->badgeTitles === null ) {
			$this->badgeTitles = $this->lookupBadgeTitles();
		}

		return $this->badgeTitles;
	}

	/**
	 * Returns the title for the given serialized badge id.
	 *
	 * @since 0.5
	 *
	 * @param string $badgeId
	 * @return string|null
	 */
	public function getBadgeTitle( $badgeId ) {
		$titles = $this->getBadgeTitles();
		return isset( $titles[$badgeId] ) ? $titles[$badgeId] : null;
	}

	/**
	 * Looks up the lables for all badges and uses them as titles.
	 *
	 * @return string[] array mapping badge ids to titles
	 */
	private function lookupBadgeTitles() {
		$itemIds = $this->getItemIds();
		$entityInfo = $this->entityInfoBuilder->buildEntityInfo( $itemIds );
		$this->entityInfoBuilder->addTerms( $entityInfo, array( 'label' ), array( $this->languageCode ) );

		$titles = array();
		foreach ( $this->badgeItems as $badgeId => $value ) {
			if ( isset( $entityInfo[$badgeId]['labels'][$this->languageCode] ) ) {
				$titles[$badgeId] = $entityInfo[$badgeId]['labels'][$this->languageCode]['value'];
			} else {
				$titles[$badgeId] = $badgeId;
			}
		}
		return $titles;
	}

	/**
	 * @return ItemId[]
	 * @throws InvalidArgumentException
	 */
	private function getItemIds() {
		$itemIds = array();
		foreach ( $this->badgeItems as $badgeId => $value ) {
			$itemIds[] = new ItemId( $badgeId );
		}
		return $itemIds;
	}

}
