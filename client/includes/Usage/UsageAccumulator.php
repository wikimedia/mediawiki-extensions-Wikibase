<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * Interface and base class for objects accumulating usage tracking information for a given page.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
abstract class UsageAccumulator {

	/**
	 * Registers usage of the given aspect of the given entity.
	 */
	abstract public function addUsage( EntityUsage $usage ): void;

	/**
	 * Registers the usage of an entity's label (in the given language).
	 */
	public function addLabelUsage( EntityId $id, ?string $languageCode = null ): void {
		$this->addUsage( new EntityUsage( $id, EntityUsage::LABEL_USAGE, $languageCode ) );
	}

	/**
	 * Registers the usage of an entity's description (in the given language).
	 */
	public function addDescriptionUsage( EntityId $id, ?string $languageCode = null ): void {
		$this->addUsage( new EntityUsage( $id, EntityUsage::DESCRIPTION_USAGE, $languageCode ) );
	}

	/**
	 * Registers the usage of an entity's local page title,
	 * i.e. the title of the local (client) page linked to the entity,
	 * e.g. to refer to the corresponding page on the local wiki.
	 */
	public function addTitleUsage( EntityId $id ): void {
		$this->addUsage( new EntityUsage( $id, EntityUsage::TITLE_USAGE ) );
	}

	/**
	 * Registers the usage of an entity's sitelinks, e.g. to generate language links.
	 */
	public function addSiteLinksUsage( EntityId $id ): void {
		$this->addUsage( new EntityUsage( $id, EntityUsage::SITELINK_USAGE ) );
	}

	/**
	 * Registers the usage of statements from an entity (identified by their property id).
	 *
	 * @param EntityId $id
	 * @param NumericPropertyId $propertyId The NumericPropertyId of Statements that are used.
	 */
	public function addStatementUsage( EntityId $id, NumericPropertyId $propertyId ): void {
		$this->addUsage( new EntityUsage( $id, EntityUsage::STATEMENT_USAGE, $propertyId->getSerialization() ) );
	}

	/**
	 * Registers the usage of other (i.e. not label, sitelink, or title) of an
	 * entity (e.g. access to statements or labels in labels a language other
	 * than the content language).
	 */
	public function addOtherUsage( EntityId $id ): void {
		$this->addUsage( new EntityUsage( $id, EntityUsage::OTHER_USAGE ) );
	}

	/**
	 * Please do not add new usages of "all'("X") aspects. This places too much pressure
	 * on client recentchanges tables and causes unnecessary reparses. The method only remains to monitor redirect pages
	 * @see WikibaseLanguageIndependentLuaBindings.php line 328 for an example of this.
	 *
	 * Registers the usage of any/all data of an entity (e.g. when accessed
	 * programmatically using Lua).
	 */
	public function addAllUsage( EntityId $id ): void {
		$this->addUsage( new EntityUsage( $id, EntityUsage::ALL_USAGE ) );
	}

	/**
	 * Returns all entity usages previously registered via addXxxUsage()
	 *
	 * @return EntityUsage[]
	 */
	abstract public function getUsages(): array;
}
