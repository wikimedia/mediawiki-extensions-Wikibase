<?php

namespace Wikibase\Repo\View;

use Linker;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com>
 */
class EntityInfoPropertyLinkFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 */
	public function __construct( EntityTitleLookup $entityTitleLookup ) {
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @param PropertyId $propertyId
	 * @param array[] $entityInfo
	 *
	 * @return string
	 */
	public function makePropertyLink( PropertyId $propertyId, array $entityInfo ) {
		$key = $propertyId->getSerialization();
		$propertyLabel = $key;
		if ( isset( $entityInfo[$key] ) && !empty( $entityInfo[$key]['labels'] ) ) {
			$entityInfoLabel = reset( $entityInfo[$key]['labels'] );
			$propertyLabel = $entityInfoLabel['value'];
		}

		// @todo use EntityIdHtmlLinkFormatter here
		$propertyLink = Linker::link(
			$this->entityTitleLookup->getTitleForId( $propertyId ),
			htmlspecialchars( $propertyLabel )
		);

		return $propertyLink;
	}

}
