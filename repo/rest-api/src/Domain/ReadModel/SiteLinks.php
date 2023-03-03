<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayIterator;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinks extends ArrayIterator {

	public function __construct( SiteLink ...$siteLinks ) {
		parent::__construct( $siteLinks );
	}

}
