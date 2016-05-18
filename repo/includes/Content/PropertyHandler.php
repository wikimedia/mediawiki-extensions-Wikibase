<?php

namespace Wikibase\Repo\Content;

use DataUpdate;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EditPropertyAction;
use Wikibase\EntityContent;
use Wikibase\HistoryEntityAction;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\PropertyContent;
use Wikibase\PropertyInfoBuilder;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\SubmitPropertyAction;
use Wikibase\TermIndex;
use Wikibase\Updates\DataUpdateAdapter;
use Wikibase\ViewPropertyAction;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
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
	 * @see EntityHandler::getContentClass
	 *
	 * @since 0.3
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
	 * @param PropertyInfoStore $infoStore
	 * @param PropertyInfoBuilder $propertyInfoBuilder
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		EntityPerPage $entityPerPage,
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		PropertyInfoStore $infoStore,
		PropertyInfoBuilder $propertyInfoBuilder,
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
			$legacyExportFormatDetector
		);

		$this->infoStore = $infoStore;
		$this->propertyInfoBuilder = $propertyInfoBuilder;
	}

	/**
	 * @return string[]
	 */
	public function getActionOverrides() {
		return array(
			'history' => HistoryEntityAction::class,
			'view' => ViewPropertyAction::class,
			'edit' => EditPropertyAction::class,
			'submit' => SubmitPropertyAction::class,
		);
	}

	/**
	 * @see EntityHandler::getSpecialPageForCreation
	 * @since 0.2
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
	 * @since 0.5
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
	 * @since 0.5
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
