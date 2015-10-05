<?php

namespace Wikibase\Repo\DataUpdates;

use Hooks;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;

/**
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserOutputDataUpdatesFactory {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $externalEntityIdParser;

	/**
	 * @var ParserOutputDataUpdate[]
	 */
	private $dataUpdates = null;

	public function __construct(
		PropertyDataTypeMatcher $propertyDataTypeMatcher,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $externalEntityIdParser
	) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->externalEntityIdParser = $externalEntityIdParser;
	}

	/**
	 * @return ParserOutputDataUpdate[]
	 */
	public function getDataUpdates() {
		if ( $this->dataUpdates === null ) {
			$this->dataUpdates = $this->newDataUpdates();
		}

		return $this->dataUpdates;
	}

	private function newDataUpdates() {
		$dataUpdates = array(
			new ReferencedEntitiesDataUpdate(
				$this->entityTitleLookup,
				$this->externalEntityIdParser
			),
			new ExternalLinksDataUpdate( $this->propertyDataTypeMatcher ),
			new ImageLinksDataUpdate( $this->propertyDataTypeMatcher )
		);

		if ( class_exists( 'CoordinatesOutput' ) ) {
			$dataUpdates[] = new GeoDataDataUpdate( $this->propertyDataTypeMatcher );
		}

		return $dataUpdates;
	}

}
