<?php

namespace Wikibase\Repo\Content;

use DataUpdate;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\SiteLinkCache;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\TermIndex;
use Wikibase\Updates\DataUpdateAdapter;
use Wikibase\Validators\EntityValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemHandler extends EntityHandler {

	/**
	 * @var SiteLinkCache
	 */
	private $siteLinkStore;

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityValidator[] $preSaveValidators
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param SiteLinkCache $siteLinkStore
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		EntityPerPage $entityPerPage,
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		array $preSaveValidators,
		ValidatorErrorLocalizer $errorLocalizer,
		SiteLinkCache $siteLinkStore,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			CONTENT_MODEL_WIKIBASE_ITEM,
			$entityPerPage,
			$termIndex,
			$contentCodec,
			$preSaveValidators,
			$errorLocalizer,
			$legacyExportFormatDetector
		);

		$this->siteLinkStore = $siteLinkStore;
	}

	/**
	 * @see EntityHandler::getContentClass
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\ItemContent';
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'history' => '\Wikibase\HistoryItemAction',
			'view' => '\Wikibase\ViewItemAction',
			'edit' => '\Wikibase\EditItemAction',
			'submit' => '\Wikibase\SubmitItemAction',
		);
	}

	/**
	 * @see EntityHandler::getSpecialPageForCreation
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getSpecialPageForCreation() {
		return 'NewItem';
	}

	/**
	 * Returns Item::ENTITY_TYPE
	 *
	 * @return string
	 */
	public function getEntityType() {
		return Item::ENTITY_TYPE;
	}

	/**
	 * Returns deletion updates for the given EntityContent.
	 *
	 * @see EntityHandler::getEntityDeletionUpdates
	 *
	 * @since 0.5
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityDeletionUpdates( EntityContent $content, Title $title ) {
		$updates = array();

		$updates[] = new DataUpdateAdapter(
			array( $this->siteLinkStore, 'deleteLinksOfItem' ),
			$content->getEntityId()
		);

		return array_merge(
			parent::getEntityDeletionUpdates( $content, $title ),
			$updates
		);
	}

	/**
	 * Returns modification updates for the given EntityContent.
	 *
	 * @see EntityHandler::getEntityModificationUpdates
	 *
	 * @since 0.5
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityModificationUpdates( EntityContent $content, Title $title ) {
		$updates = array();

		if ( $content->isRedirect() ) {
			$updates[] = new DataUpdateAdapter(
				array( $this->siteLinkStore, 'deleteLinksOfItem' ),
				$content->getEntityId()
			);
		} else {
			$updates[] = new DataUpdateAdapter(
				array( $this->siteLinkStore, 'saveLinksOfItem' ),
				$content->getEntity()
			);
		}

		return array_merge(
			$updates,
			parent::getEntityModificationUpdates( $content, $title )
		);
	}

	/**
	 * @see EntityHandler::makeEmptyEntity()
	 *
	 * @since 0.5
	 *
	 * @return EntityContent
	 */
	public function makeEmptyEntity() {
		return Item::newEmpty();
	}

	/**
	 * @see EntityContent::makeEntityId
	 *
	 * @param string $id
	 *
	 * @return EntityId
	 */
	public function makeEntityId( $id ) {
		return new ItemId( $id );
	}

}
