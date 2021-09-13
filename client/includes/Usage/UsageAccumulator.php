<?php

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
	 *
	 * @param EntityUsage $usage
	 */
	abstract public function addUsage( EntityUsage $usage );

	/**
	 * Registers the usage of an entity's label (in the given language).
	 *
	 * @param EntityId $id
	 * @param string|null $languageCode
	 */
	public function addLabelUsage( EntityId $id, $languageCode = null ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::LABEL_USAGE, $languageCode ) );
	}

	/**
	 * Registers the usage of an entity's description (in the given language).
	 *
	 * @param EntityId $id
	 * @param string|null $languageCode
	 */
	public function addDescriptionUsage( EntityId $id, $languageCode = null ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::DESCRIPTION_USAGE, $languageCode ) );
	}

	/**
	 * Registers the usage of an entity's local page title,
	 * i.e. the title of the local (client) page linked to the entity,
	 * e.g. to refer to the corresponding page on the local wiki.
	 *
	 * @param EntityId $id
	 */
	public function addTitleUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::TITLE_USAGE ) );
	}

	/**
	 * Registers the usage of an entity's sitelinks, e.g. to generate language links.
	 *
	 * @param EntityId $id
	 */
	public function addSiteLinksUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::SITELINK_USAGE ) );
	}

	/**
	 * Registers the usage of statements from an entity (identified by their property id).
	 *
	 * @param EntityId $id
	 * @param NumericPropertyId $propertyId The NumericPropertyId of Statements that are used.
	 */
	public function addStatementUsage( EntityId $id, NumericPropertyId $propertyId ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::STATEMENT_USAGE, $propertyId->getSerialization() ) );
	}

	/**
	 * Registers the usage of other (i.e. not label, sitelink, or title) of an
	 * entity (e.g. access to statements or labels in labels a language other
	 * than the content language).
	 *
	 * @param EntityId $id
	 */
	public function addOtherUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::OTHER_USAGE ) );
	}

	/**
	 * Registers the usage of any/all data of an entity (e.g. when accessed
	 * programmatically using Lua).
	 *
	 * @param EntityId $id
	 */
	public function addAllUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::ALL_USAGE ) );
	}

	/**
	 * Returns all entity usages previously registered via addXxxUsage()
	 *
	 * @return EntityUsage[]
	 */
	abstract public function getUsages();

}
