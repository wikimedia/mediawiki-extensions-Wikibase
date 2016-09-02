<?php

namespace Wikibase\Client\Usage;

use ArrayIterator;
use InvalidArgumentException;
use Traversable;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * UsageLookup implementation based on an EntityUsage.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class EntityUsageLookup implements UsageLookup {

	/**
	 * @var SqlUsageTracker
	 */
	protected $sqlUsageTracker;

	/**
	 * @param string $clientSiteId The local wiki's global site id
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param TitleFactory $titleFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( SqlUsageTracker $sqlUsageTracker ) {
		$this->sqlUsageTracker = $sqlUsageTracker;
	}

	/**
	 * @see UsageLookup::getUsagesForPage
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 * @throws UsageTrackerException
	 */
	public function getUsagesForPage( $pageId ) {
		return $this->sqlUsageTracker->getUsagesForPage( $pageId );
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects Which aspects to consider (if omitted, all aspects are considered).
	 * Use the EntityUsage::XXX_USAGE constants to represent aspects.
	 *
	 * @return Traversable of PageEntityUsages
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entityIds, array $aspects = array() ) {
		return $this->sqlUsageTracker->getPagesUsing( $entityIds, $aspects );
	}

	/**
	 * @see UsageLookup::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[] A list of elements of $entities that are unused.
	 */
	public function getUnusedEntities( array $entityIds ) {
		return $this->sqlUsageTracker->getUnusedEntities( $entityIds );
	}

}
