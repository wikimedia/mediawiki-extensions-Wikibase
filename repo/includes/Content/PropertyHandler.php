<?php

namespace Wikibase\Repo\Content;

use Article;
use Content;
use IContextSource;
use MediaWiki\Revision\SlotRenderingProvider;
use ParserOptions;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\Actions\EditEntityAction;
use Wikibase\Repo\Actions\HistoryEntityAction;
use Wikibase\Repo\Actions\SubmitEntityAction;
use Wikibase\Repo\Actions\ViewEntityAction;
use Wikibase\Repo\PropertyInfoBuilder;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * Content handler for Wikibase items.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyHandler extends EntityHandler {

	/**
	 * @var PropertyInfoStore
	 */
	private $infoStore;

	/**
	 * @var PropertyInfoBuilder
	 */
	private $propertyInfoBuilder;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var FallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @var EntityTermStoreWriter
	 */
	private $entityTermStoreWriter;

	/**
	 * @param EntityTermStoreWriter $entityTermStoreWriter
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdLookup $entityIdLookup
	 * @param FallbackLabelDescriptionLookupFactory $labelLookupFactory
	 * @param PropertyInfoStore $infoStore
	 * @param PropertyInfoBuilder $propertyInfoBuilder
	 * @param FieldDefinitions $propertyFieldDefinitions
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		EntityTermStoreWriter $entityTermStoreWriter,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		FallbackLabelDescriptionLookupFactory $labelLookupFactory,
		PropertyInfoStore $infoStore,
		PropertyInfoBuilder $propertyInfoBuilder,
		FieldDefinitions $propertyFieldDefinitions,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			PropertyContent::CONTENT_MODEL_ID,
			null,
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
			$propertyFieldDefinitions,
			$legacyExportFormatDetector
		);

		$this->entityIdLookup = $entityIdLookup;
		$this->labelLookupFactory = $labelLookupFactory;
		$this->infoStore = $infoStore;
		$this->propertyInfoBuilder = $propertyInfoBuilder;
		$this->entityTermStoreWriter = $entityTermStoreWriter;
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
		return 'NewProperty';
	}

	/**
	 * Returns Property::ENTITY_TYPE
	 *
	 * @return string
	 */
	public function getEntityType() {
		return Property::ENTITY_TYPE;
	}

	public function getSecondaryDataUpdates(
		Title $title,
		Content $content,
		$role,
		SlotRenderingProvider $slotOutput
	) {
		$updates = parent::getSecondaryDataUpdates( $title, $content, $role, $slotOutput );

		/** @var PropertyContent $content */
		'@phan-var PropertyContent $content';
		$id = $content->getEntityId();
		$property = $content->getProperty();

		$updates[] = new DataUpdateAdapter(
			[ $this->infoStore, 'setPropertyInfo' ],
			$id,
			$this->propertyInfoBuilder->buildPropertyInfo( $property )
		);

		if ( $content->isRedirect() ) {
			$updates[] = new DataUpdateAdapter(
				[ $this->entityTermStoreWriter, 'deleteTermsOfEntity' ],
				$id
			);
		} else {
			$updates[] = new DataUpdateAdapter(
				[ $this->entityTermStoreWriter, 'saveTermsOfEntity' ],
				$property
			);
		}

		return $updates;
	}

	public function getDeletionUpdates( Title $title, $role ) {
		$updates = parent::getDeletionUpdates( $title, $role );

		$id = $this->getIdForTitle( $title );

		$updates[] = new DataUpdateAdapter(
			[ $this->infoStore, 'removePropertyInfo' ],
			$id
		);

		// Unregister the entity from the term store.
		$updates[] = new DataUpdateAdapter(
			[ $this->entityTermStoreWriter, 'deleteTermsOfEntity' ],
			$id
		);

		return $updates;
	}

	/**
	 * @see EntityHandler::makeEmptyEntity()
	 *
	 * @return Property
	 */
	public function makeEmptyEntity() {
		return Property::newFromType( '' );
	}

	/**
	 * @see EntityHandler::newEntityContent
	 *
	 * @param EntityHolder|null $entityHolder
	 *
	 * @return PropertyContent
	 */
	protected function newEntityContent( EntityHolder $entityHolder = null ) {
		return new PropertyContent( $entityHolder );
	}

	/**
	 * @see EntityContent::makeEntityId
	 *
	 * @param string $id
	 *
	 * @return EntityId
	 */
	public function makeEntityId( $id ) {
		return new NumericPropertyId( $id );
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
