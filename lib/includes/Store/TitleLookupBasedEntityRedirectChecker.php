<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityRedirectChecker implements EntityRedirectChecker {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct( EntityTitleLookup $titleLookup ) {
		$this->titleLookup = $titleLookup;
	}

	public function isRedirect( EntityId $id ): bool {
		$title = $this->titleLookup->getTitleForId( $id );

		return $title && $title->isLocal() && $title->isRedirect();
	}

}
