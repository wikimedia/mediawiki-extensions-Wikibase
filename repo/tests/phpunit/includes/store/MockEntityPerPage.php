<?php

namespace Wikibase\Test;

use FakeResultWrapper;
use Wikibase\DatabaseRowEntityIdIterator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityContent;
use Wikibase\EntityFactory;
use Wikibase\EntityId;
use Wikibase\EntityPerPage;
use Wikibase\Item;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MockEntityPerPage implements EntityPerPage {

	protected $rows = array();

	/**
	 * @param EntityContent $entityContent
	 *
	 * @return boolean Success indicator
	 */
	public function addEntityContent( EntityContent $entityContent ) {
		if ( $this->hasEntityContent( $entityContent ) ) {
			return false;
		}

		$entity = $entityContent->getEntity();
		$entityId = $entity->getId();
		$prefixedId = $entityId->getSerialization();

		$this->rows[$prefixedId] = array(
			'epp_entity_id' => $entityId->getNumericId(),
			'epp_entity_type' => $entity->getType(),
			'epp_page_id' => $entityContent->getTitle()->getArticleID()
		);

		return true;
	}

	protected function hasEntityContent( EntityContent $entityContent ) {
		$entityId = $entityContent->getEntity()->getId()->getSerialization();

		return array_key_exists( $entityId, $this->rows );
	}

	/**
	 * @param EntityContent $entityContent
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntityContent( EntityContent $entityContent ) {
		$entityId = $entityContent->getEntity()->getId();

		return $this->deleteEntityById( $entityId );
	}

	/**
	 * @param EntityId
	 *
	 * @return boolean
	 */
	public function deleteEntityById( EntityId $entityId ) {
		$prefixedId = $entityId->getSerialization();

		unset( $this->rows[$prefixedId] );

		return true;
	}

	/**
	 * @return boolean Success indicator
	 */
	public function clear() {
		$this->rows = array();

		return true;
	}

	/**
	 * @see EntityPerPage::rebuild
	 */
	public function rebuild() {
		// todo?
	}

	/**
	 * @see EntityPerPage::getEntitiesWithoutTerm
	 */
	public function getEntitiesWithoutTerm( $termType, $language = null, $entityType = null, $limit = 50, $offset = 0 ) {
		// todo?
	}

	/**
	 * @see EntityPerPage::getItemsWithoutSitelinks
	 */
	public function getItemsWithoutSitelinks( $siteId = null, $limit = 50, $offset = 0 ) {
		// todo?
	}

	/**
	 * @see EntityPerPage::getEntities
	 */
	public function getEntities( $entityType = null ) {
		$entityRows = array();

		foreach( $this->rows as $row ) {
			if ( $entityType === $row['epp_entity_type'] || $entityType === null ) {
				$entityRows[] = $row;
			}
		}

		$result = new FakeResultWrapper( $entityRows );

		return new DatabaseRowEntityIdIterator(
			$result,
			'epp_entity_type',
			'epp_entity_id'
		);
	}
}
