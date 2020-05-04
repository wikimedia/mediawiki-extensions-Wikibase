<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityUrlLookup implements EntityUrlLookup {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct( EntityTitleLookup $titleLookup ) {
		$this->titleLookup = $titleLookup;
	}

	public function getFullUrl( EntityId $id ): ?string {
		$title = $this->titleLookup->getTitleForId( $id );
		return $title ? $title->getFullURL() : null;
	}

	public function getLinkUrl( EntityId $id ): ?string {
		$title = $this->titleLookup->getTitleForId( $id );
		return $title ? $title->getLinkURL() : null;
	}
}
