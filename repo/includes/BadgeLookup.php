<?php

namespace Wikibase\Repo;

use Language;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityInfoBuilder;

/**
 * Looks up badges
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

	public function __construct( Language $language, array $badgeItems, EntityInfoBuilder $entityInfoBuilder ) {
		$this->languageCode = $language->getCode();
		$this->badgeItems = $badgeItems;
		$this->entityInfoBuilder = $entityInfoBuilder;
	}

	/**
	 * Returns the titles for all available badges.
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
	 * Returns the title for the given badge id.
	 *
	 * @param ItemId $badgeId
	 * @return string|null
	 */
	public function getBadgeTitle( ItemId $badgeId ) {
		$titles = $this->getBadgeTitles();
		$badgeId = $badgeId->getSerialization();
		return isset( $titles[$badgeId] ) ? $titles[$badgeId] : null;
	}

	/**
	 * Looksup the lables for all badges and uses them as titles.
	 *
	 * @return string[] array mapping badge ids to titles
	 * @throws InvalidArgumentException
	 */
	private function lookupBadgeTitles() {
		$itemIds = array();
		foreach ( $this->badgeItems as $badgeId => $value ) {
			$itemIds[] = new ItemId( $badgeId );
		}

		$entityInfo = $this->entityInfoBuilder->buildEntityInfo( $itemIds );
		$this->entityInfoBuilder->addTerms( $entityInfo, array( 'label' ), array( $this->languageCode ) );

		$titles = array();
		foreach ( $this->badgeItems as $badgeId => $value ) {
			$titles[$badgeId] = isset( $entityInfo[$badgeId]['labels'][$this->languageCode] ) ?
				$entityInfo[$badgeId]['labels'][$this->languageCode]['value'] : $badgeId;
		}

		return $titles;
	}

}
