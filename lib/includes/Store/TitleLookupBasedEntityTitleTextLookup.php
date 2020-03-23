<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityTitleTextLookup implements EntityTitleTextLookup {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct( EntityTitleLookup $titleLookup ) {
		$this->titleLookup = $titleLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function getPrefixedText( EntityId $id ): ?string {
		return $this->titleLookup->getTitleForId( $id )->getPrefixedText();
	}
}
