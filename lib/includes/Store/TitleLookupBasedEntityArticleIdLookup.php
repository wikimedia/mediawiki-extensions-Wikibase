<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityArticleIdLookup implements EntityArticleIdLookup {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct( EntityTitleLookup $titleLookup ) {
		$this->titleLookup = $titleLookup;
	}

	public function getArticleId( EntityId $id ): int {
		return $this->titleLookup->getTitleForId( $id )->getArticleID();
	}
}
