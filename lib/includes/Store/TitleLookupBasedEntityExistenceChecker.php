<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityExistenceChecker implements EntityExistenceChecker {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct( EntityTitleLookup $titleLookup ) {
		$this->titleLookup = $titleLookup;
	}

	public function exists( EntityId $id ): bool {
		$title = $this->titleLookup->getTitleForId( $id );

		return $title !== null && $title->isKnown();
	}

}
