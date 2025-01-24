<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Domain\ReadModel;

use ArrayObject;

/**
 * @license GPL-2.0-or-later
 */
class Sitelinks extends ArrayObject {

	public function __construct( Sitelink ...$sitelinks ) {
		parent::__construct(
			array_combine(
				array_map( fn( Sitelink $sitelink ) => $sitelink->getSiteId(), $sitelinks ),
				$sitelinks
			)
		);
	}

}
