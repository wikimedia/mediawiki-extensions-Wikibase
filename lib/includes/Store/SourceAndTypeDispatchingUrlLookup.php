<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingUrlLookup implements EntityUrlLookup {

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

	public function getFullUrl( EntityId $id ): ?string {
		return $this->serviceDispatcher->getServiceForSourceAndType(
			$this->sourceLookup->getEntitySourceById( $id )->getSourceName(),
			$id->getEntityType()
		)->getFullUrl( $id );
	}

	public function getLinkUrl( EntityId $id ): ?string {
		return $this->serviceDispatcher->getServiceForSourceAndType(
			$this->sourceLookup->getEntitySourceById( $id )->getSourceName(),
			$id->getEntityType()
		)->getLinkUrl( $id );
	}

}
