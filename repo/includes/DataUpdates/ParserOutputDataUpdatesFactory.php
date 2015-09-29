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
			$this->initDataUpdates();
		}

		return $this->dataUpdates;
	}

	private function initDataUpdates() {
		$dataUpdates = array();

		// @todo document the hook!
		Hooks::run( 'WikibaseParserOutputDataUpdates', array( $this, &$dataUpdates ) );

		$propertyDataTypeMatcher = $this->getPropertyDataTypeMatcher();

		$this->dataUpdates = array_merge(
			array(
				new ReferencedEntitiesDataUpdate(
					$this->entityTitleLookup,
					$this->externalEntityIdParser
				),
				new ExternalLinksDataUpdate( $propertyDataTypeMatcher ),
				new ImageLinksDataUpdate( $propertyDataTypeMatcher )
			),
			$dataUpdates
		);
	}

	/**
	 * @return PropertyDataTypeMatcher
	 */
	public function getPropertyDataTypeMatcher() {
		return $this->propertyDataTypeMatcher;
	}

}
