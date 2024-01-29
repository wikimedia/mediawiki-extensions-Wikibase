<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayObject;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinks extends ArrayObject {

	public function __construct( SiteLink ...$sitelinks ) {
		parent::__construct(
			array_combine(
				array_map( fn( SiteLink $siteLink ) => $siteLink->getSite(), $sitelinks ),
				$sitelinks
			)
		);
	}

}
