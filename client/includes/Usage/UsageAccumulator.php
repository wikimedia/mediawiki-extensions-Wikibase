<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\Snak;

/**
 * Interface for objects accumulating usage tracking information for a given page.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface UsageAccumulator {

	/**
	 * Registers the usage of an entity's label (in the local content language).
	 *
	 * @param EntityId $id
	 */
	public function addLabelUsage( EntityId $id );

	/**
	 * Registers the usage of entity's labels (in the local content language), if the provided
	 * snaks are PropertyValueSnaks that contain EntityIdValues.
	 *
	 * @param Snak[] $snaks
	 */
	public function addLabelUsageForSnaks( array $snaks );

	/**
	 * Registers the usage of an entity's local page title, e.g. to refer to
	 * the corresponding page on the local wiki.
	 *
	 * @param EntityId $id
	 */
	public function addTitleUsage( EntityId $id );

	/**
	 * Registers the usage of an entity's sitelinks, e.g. to generate language links.
	 *
	 * @param EntityId $id
	 */
	public function addSiteLinksUsage( EntityId $id );

	/**
	 * Registers the usage of other (i.e. not label, sitelink, or title) of an
	 * entity (e.g. access to statements or labels in labels a language other
	 * than the content language).
	 *
	 * @param EntityId $id
	 */
	public function addOtherUsage( EntityId $id );

	/**
	 * Registers the usage of any/all data of an entity (e.g. when accessed
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
