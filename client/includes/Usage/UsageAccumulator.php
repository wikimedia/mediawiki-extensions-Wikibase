<?php

namespace Wikibase\Client\Usage;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for objects accumulating usage tracking information for a given page.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface UsageAccumulator {

	/**
	 * Registers the usage an entity's label (in the local content language).
	 *
	 * @param EntityId $id
	 */
	public function addLabelUsage( EntityId $id );

	/**
	 * Registers the usage of an entity's local page title, e.g. to refer to
	 * the corresponding page on the local wiki.
	 *
	 * @param EntityId $id
	 */
	public function addPageUsage( EntityId $id );

	/**
	 * Registers the usage of an entity's sitelinks, e.g. to generate language links.
	 *
	 * @param EntityId $id
	 */
	public function addSitelinksUsage( EntityId $id );

	/**
	 * Registers the usage of other or all data of an entity (e.g. when accessed
	 * programmatically using Lua).
	 *
	 * @param EntityId $id
	 */
	public function addAllUsage( EntityId $id );

	/**
	 * Returns all entity usages previously registered via addXxxUsage()
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages();

}
