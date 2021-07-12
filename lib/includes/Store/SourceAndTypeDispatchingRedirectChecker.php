<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingRedirectChecker implements EntityRedirectChecker {

	/**
	 * @var ServiceBySourceAndTypeDispatcher
	 */
	private $serviceDispatcher;

	/**
	 * @var EntitySourceLookup
	 */
	private $sourceLookup;

	public function __construct( ServiceBySourceAndTypeDispatcher $serviceDispatcher, EntitySourceLookup $sourceLookup ) {
		$this->serviceDispatcher = $serviceDispatcher;
		$this->sourceLookup = $sourceLookup;
	}

	public function isRedirect( EntityId $id ): bool {
		return $this->serviceDispatcher->getServiceForSourceAndType(
			$this->sourceLookup->getEntitySourceById( $id )->getSourceName(),
			$id->getEntityType()
		)->isRedirect( $id );
	}

}
