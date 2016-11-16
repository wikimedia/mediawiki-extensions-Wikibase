<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Store\EntityTitleStoreLookup;

/**
 * Formats entity IDs by generating the corresponding page title.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityIdTitleFormatter implements EntityIdFormatter {

	/**
	 * @var EntityTitleStoreLookup
	 */
	protected $titleLookup;

	/**
	 * @param EntityTitleStoreLookup $titleLookup
	 */
	public function __construct( EntityTitleStoreLookup $titleLookup ) {
		$this->titleLookup = $titleLookup;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string Plain text
	 */
	public function formatEntityId( EntityId $entityId ) {
		$title = $this->titleLookup->getTitleForId( $entityId );
		return $title->getFullText();
	}

}
