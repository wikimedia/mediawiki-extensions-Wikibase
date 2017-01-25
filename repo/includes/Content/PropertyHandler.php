<?php

namespace Wikibase\Repo\Content;

use DataUpdate;
use IContextSource;
use Page;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EditEntityAction;
use Wikibase\EntityContent;
use Wikibase\HistoryEntityAction;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\PropertyContent;
use Wikibase\PropertyInfoBuilder;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\Search\Elastic\Fields\PropertyFieldDefinitions;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Store\EntityIdLookup;
use Wikibase\SubmitEntityAction;
use Wikibase\TermIndex;
use Wikibase\ViewEntityAction;

/**
 * Content handler for Wikibase items.
 *
 * @license GPL-2.0+
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
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @see EntityHandler::getContentClass
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return PropertyContent::class;
	}

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdLookup $entityIdLookup
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory
	 * @param PropertyInfoStore $infoStore
	 * @param PropertyInfoBuilder $propertyInfoBuilder
	 * @param PropertyFieldDefinitions $propertyFieldDefinitions
	 * @param callable|null $legacyExportFormatDetector
	 * @internal param PropertyFieldDefinitions $itemFieldDefinitions
	 */
	public function __construct(
		EntityPerPage $entityPerPage,
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory,
		PropertyInfoStore $infoStore,
		PropertyInfoBuilder $propertyInfoBuilder,
		PropertyFieldDefinitions $propertyFieldDefinitions,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			CONTENT_MODEL_WIKIBASE_PROPERTY,
			$entityPerPage,
			$termIndex,
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
	}

	/**
	 * @return string[]
	 */
	public function getActionOverrides() {
		return array(
			'history' => function( Page $page, IContextSource $context = null ) {
				return new HistoryEntityAction(
					$page,
					$context,
					$this->entityIdLookup,
					$this->labelLookupFactory->newLabelDescriptionLookup( $context->getLanguage() )
				);
			},
			'view' => ViewEntityAction::class,
			'edit' => EditEntityAction::class,
			'submit' => SubmitEntityAction::class,
		);
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
		$updates = array();

		$updates[] = new DataUpdateAdapter(
			array( $this->infoStore, 'removePropertyInfo' ),
			$content->getEntity()->getId()
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
		/** @var PropertyContent $content */
		$updates = array();

		$info = $this->propertyInfoBuilder->buildPropertyInfo( $content->getProperty() );

		$updates[] = new DataUpdateAdapter(
			array( $this->infoStore, 'setPropertyInfo' ),
			$content->getEntity()->getId(),
			$info
		);

		return array_merge(
			$updates,
			parent::getEntityModificationUpdates( $content, $title )
		);
	}

	/**
	 * @see EntityHandler::makeEmptyEntity()
	 *
	 * @return EntityContent
	 */
	public function makeEmptyEntity() {
		return Property::newFromType( '' );
	}

	/**
	 * @see EntityContent::makeEntityId
	 *
	 * @param string $id
	 *
	 * @return EntityId
	 */
	public function makeEntityId( $id ) {
		return new PropertyId( $id );
	}

}
