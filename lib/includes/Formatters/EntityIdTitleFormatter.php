<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Formats entity IDs by generating the corresponding page title.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdTitleFormatter implements EntityIdFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	protected $titleLookup;

	public function __construct( EntityTitleLookup $titleLookup ) {
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
