<?php

namespace Wikibase\Repo\Content;

use DataUpdate;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EditItemAction;
use Wikibase\EntityContent;
use Wikibase\HistoryEntityAction;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\SubmitItemAction;
use Wikibase\TermIndex;
use Wikibase\Updates\DataUpdateAdapter;
use Wikibase\ViewItemAction;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemHandler extends EntityHandler {

	/**
	 * @var SiteLinkStore
	 */
	private $siteLinkStore;

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param SiteLinkStore $siteLinkStore
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		EntityPerPage $entityPerPage,
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		SiteLinkStore $siteLinkStore,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			CONTENT_MODEL_WIKIBASE_ITEM,
			$entityPerPage,
			$termIndex,
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
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
		return ItemContent::class;
	}

	/**
	 * @return string[]
	 */
	public function getActionOverrides() {
		return array(
			'history' => HistoryEntityAction::class,
			'view' => ViewItemAction::class,
			'edit' => EditItemAction::class,
			'submit' => SubmitItemAction::class,
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
		return new Item();
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
