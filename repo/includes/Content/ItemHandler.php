<?php

namespace Wikibase\Repo\Content;

use Article;
use Content;
use IContextSource;
use MediaWiki\Revision\SlotRenderingProvider;
use ParserOptions;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Repo\Actions\EditEntityAction;
use Wikibase\Repo\Actions\HistoryEntityAction;
use Wikibase\Repo\Actions\SubmitEntityAction;
use Wikibase\Repo\Actions\ViewEntityAction;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * Content handler for Wikibase items.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ItemHandler extends EntityHandler {

	/**
	 * @var SiteLinkStore
	 */
	private $siteLinkStore;

	/** @var BagOStuffSiteLinkConflictLookup */
	private $bagOStuffSiteLinkConflictLookup;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var FallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var EntityTermStoreWriter
	 */
	private $entityTermStoreWriter;

	/** @var RepoDomainDb */
	private $db;

	/** @var bool[] */
	private $isExternalIdByPropertyId;

	public function __construct(
		EntityTermStoreWriter $entityTermStoreWriter,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		SiteLinkStore $siteLinkStore,
		BagOStuffSiteLinkConflictLookup $bagOStuffSiteLinkConflictLookup,
		EntityIdLookup $entityIdLookup,
		FallbackLabelDescriptionLookupFactory $labelLookupFactory,
		FieldDefinitions $itemFieldDefinitions,
		PropertyDataTypeLookup $dataTypeLookup,
		RepoDomainDb $db,
		?callable $legacyExportFormatDetector = null
	) {
		parent::__construct(
			ItemContent::CONTENT_MODEL_ID,
			null,
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
			$itemFieldDefinitions,
			$legacyExportFormatDetector
		);

		$this->entityIdLookup = $entityIdLookup;
		$this->labelLookupFactory = $labelLookupFactory;
		$this->siteLinkStore = $siteLinkStore;
		$this->bagOStuffSiteLinkConflictLookup = $bagOStuffSiteLinkConflictLookup;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityTermStoreWriter = $entityTermStoreWriter;
		$this->db = $db;
	}

	/**
	 * @return (\Closure|class-string)[]
	 */
	public function getActionOverrides() {
		return [
			'history' => function ( Article $article, IContextSource $context ) {
				return new HistoryEntityAction(
					$article,
					$context,
					$this->entityIdLookup,
					$this->labelLookupFactory->newLabelDescriptionLookup( $context->getLanguage() )
				);
			},
			'view' => ViewEntityAction::class,
			'edit' => EditEntityAction::class,
			'submit' => SubmitEntityAction::class,
		];
	}

	/**
	 * @see EntityHandler::getSpecialPageForCreation
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

	public function getSecondaryDataUpdates(
		Title $title,
		Content $content,
		$role,
		SlotRenderingProvider $slotOutput
	) {
		$updates = parent::getSecondaryDataUpdates( $title, $content, $role, $slotOutput );

		/** @var ItemContent $content */
		'@phan-var ItemContent $content';
		$id = $content->getEntityId();

		if ( $content->isRedirect() ) {
			$updates[] = new DataUpdateAdapter(
				[ $this->siteLinkStore, 'deleteLinksOfItem' ],
				$id
			);
			$updates[] = new DataUpdateAdapter(
				[ $this->entityTermStoreWriter, 'deleteTermsOfEntity' ],
				$id
			);
		} else {
			/** @var ItemContent $content */
			'@phan-var ItemContent $content';
			$item = $content->getItem();

			$updates[] = new DataUpdateAdapter(
				[ $this->entityTermStoreWriter, 'saveTermsOfEntity' ],
				$item
			);

			$updates[] = new DataUpdateAdapter(
				function ( Item $item, string $method ) {
					$this->siteLinkStore->saveLinksOfItem( $item );
					$this->db->connections()->getWriteConnection()
						->onTransactionCommitOrIdle( function() use ( $item ) {
							$this->bagOStuffSiteLinkConflictLookup->clearConflictsForItem( $item );
						}, $method );
				},
				$item,
				__METHOD__
			);
		}

		return $updates;
	}

	public function getDeletionUpdates( Title $title, $role ) {
		$updates = parent::getDeletionUpdates( $title, $role );

		$id = $this->getIdForTitle( $title );

		// Unregister the entity from the term store.
		$updates[] = new DataUpdateAdapter(
			[ $this->entityTermStoreWriter, 'deleteTermsOfEntity' ],
			$id
		);

		$updates[] = new DataUpdateAdapter(
			[ $this->siteLinkStore, 'deleteLinksOfItem' ],
			$id
		);

		return $updates;
	}

	/**
	 * @see EntityHandler::makeEmptyEntity()
	 *
	 * @return EntityDocument
	 */
	public function makeEmptyEntity() {
		return new Item();
	}

	/**
	 * @see EntityHandler::makeEntityRedirectContent
	 *
	 * @param EntityRedirect $redirect
	 *
	 * @return ItemContent
	 */
	public function makeEntityRedirectContent( EntityRedirect $redirect ) {
		$title = $this->getTitleForId( $redirect->getTargetId() );
		return ItemContent::newFromRedirect( $redirect, $title );
	}

	/**
	 * @see EntityHandler::supportsRedirects
	 *
	 * @return bool Always true.
	 */
	public function supportsRedirects() {
		return true;
	}

	/**
	 * @see EntityHandler::newEntityContent
	 *
	 * @param EntityHolder|null $entityHolder
	 *
	 * @return ItemContent
	 */
	protected function newEntityContent( EntityHolder $entityHolder = null ) {
		return new ItemContent( $entityHolder );
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

	/**
	 * @param StatementList $statementList
	 * @return int
	 */
	public function getIdentifiersCount( StatementList $statementList ): int {
		$identifiers = 0;
		foreach ( $statementList as $statement ) {
			$propertyId = $statement->getPropertyId();
			$propertyIdSerialization = $propertyId->getSerialization();
			if ( !isset( $this->isExternalIdByPropertyId[$propertyIdSerialization] ) ) {
				try {
					$this->isExternalIdByPropertyId[$propertyIdSerialization] =
						$this->dataTypeLookup->getDataTypeIdForProperty( $propertyId ) === 'external-id';
				} catch ( PropertyDataTypeLookupException $e ) {
					$this->isExternalIdByPropertyId[$propertyIdSerialization] = false;
					continue;
				}
			}

			if ( $this->isExternalIdByPropertyId[$propertyIdSerialization] ) {
				$identifiers++;
			}
		}

		return $identifiers;
	}

	/**
	 * @inheritDoc
	 */
	protected function getParserOutputFromEntityView(
		EntityContent $content,
		$revisionId,
		ParserOptions $options,
		$generateHtml = true
	) {
		$parserOutput = parent::getParserOutputFromEntityView( $content, $revisionId, $options, $generateHtml );
		$parserOutput->recordOption( 'termboxVersion' );
		return $parserOutput;
	}
}
