<?php

namespace Wikibase\Repo\Content;

use Article;
use DataUpdate;
use IContextSource;
use Page;
use Title;
use Wikibase\Content\EntityHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\EditEntityAction;
use Wikibase\EntityContent;
use Wikibase\HistoryEntityAction;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Store\EntityIdLookup;
use Wikibase\SubmitEntityAction;
use Wikibase\TermIndex;
use Wikibase\ViewEntityAction;
use WikiPage;

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

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param SiteLinkStore $siteLinkStore
	 * @param EntityIdLookup $entityIdLookup
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory
	 * @param FieldDefinitions $itemFieldDefinitions
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		SiteLinkStore $siteLinkStore,
		EntityIdLookup $entityIdLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory,
		FieldDefinitions $itemFieldDefinitions,
		PropertyDataTypeLookup $dataTypeLookup,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			CONTENT_MODEL_WIKIBASE_ITEM,
			$termIndex,
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
		$this->dataTypeLookup = $dataTypeLookup;
	}

	/**
	 * @return string[]
	 */
	public function getActionOverrides() {
		return [
			'history' => function( Page $article, IContextSource $context ) {
				// NOTE: for now, the callback must work with a WikiPage as well as an Article
				// object. Once I0335100b2 is merged, this is no longer needed.
				if ( $article instanceof WikiPage ) {
					$article = Article::newFromWikiPage( $article, $context );
				}

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

	/**
	 * Returns deletion updates for the given EntityContent.
	 *
	 * @see EntityHandler::getEntityDeletionUpdates
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityDeletionUpdates( EntityContent $content, Title $title ) {
		$updates = [];

		$updates[] = new DataUpdateAdapter(
			[ $this->siteLinkStore, 'deleteLinksOfItem' ],
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
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityModificationUpdates( EntityContent $content, Title $title ) {
		$updates = [];

		if ( $content->isRedirect() ) {
			$updates[] = new DataUpdateAdapter(
				[ $this->siteLinkStore, 'deleteLinksOfItem' ],
				$content->getEntityId()
			);
		} else {
			$updates[] = new DataUpdateAdapter(
				[ $this->siteLinkStore, 'saveLinksOfItem' ],
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
	public function getIdentifiersCount( StatementList $statementList ) {
		$identifiers = 0;
		foreach ( $statementList->getPropertyIds() as $propertyIdSerialization => $propertyId ) {
			try {
				$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
			} catch ( PropertyDataTypeLookupException $e ) {
				continue;
			}

			if ( $dataType === 'external-id' ) {
				$identifiers += $statementList->getByPropertyId( $propertyId )->count();
			}
		}

		return $identifiers;
	}

}
