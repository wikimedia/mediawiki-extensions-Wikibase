<?php

namespace Wikibase;

use DataUpdate;
use Title;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Updates\DataUpdateClosure;
use Wikibase\Validators\EntityValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyHandler extends EntityHandler {

	/**
	 * @var PropertyInfoStore
	 */
	private $infoStore;

	/**
	 * @see EntityHandler::getContentClass
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}

	/**
	 * @see EntityHandler::getEntityViewClass
	 *
	 * @since 0.5
	 *
	 * @return string The class name.
	 */
	protected function getEntityViewClass() {
		return '\Wikibase\PropertyView';
	}

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityValidator[] $preSaveValidators
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param PropertyInfoStore $infoStore
	 */
	public function __construct(
		EntityPerPage $entityPerPage,
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		$preSaveValidators,
		ValidatorErrorLocalizer $errorLocalizer,
		PropertyInfoStore $infoStore
	) {
		parent::__construct(
			CONTENT_MODEL_WIKIBASE_PROPERTY,
			$entityPerPage,
			$termIndex,
			$contentCodec,
			$preSaveValidators,
			$errorLocalizer
		);

		$this->infoStore = $infoStore;
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'history' => '\Wikibase\HistoryPropertyAction',
			'view' => '\Wikibase\ViewPropertyAction',
			'edit' => '\Wikibase\EditPropertyAction',
			'submit' => '\Wikibase\SubmitPropertyAction',
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

		$updates[] = new DataUpdateClosure(
			array( $this->infoStore, 'removePropertyInfo' ),
			$content->getEntity()->getId()
		);

		return array_merge(
			parent::getEntityModificationUpdates( $content, $title ),
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

		//XXX: Where to encode the knowledge about how to extract an info array from a Property object?
		//     Should we have a PropertyInfo class? Or can we put this into the Property class?
		$info = array(
			PropertyInfoStore::KEY_DATA_TYPE => $content->getProperty()->getDataTypeId()
		);

		$updates[] = new DataUpdateClosure(
			array( $this->infoStore, 'setPropertyInfo' ),
			$content->getEntity()->getId(),
			$info
		);

		return array_merge(
			$updates,
			parent::getEntityModificationUpdates( $content, $title )
		);
	}

}
