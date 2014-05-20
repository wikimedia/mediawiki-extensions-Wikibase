<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Store\EntityContentDataCodec;
use Wikibase\Validators\EntityValidator;
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
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityValidator[] $preSaveValidators
	 */
	public function __construct( EntityContentDataCodec $contentCodec, $preSaveValidators ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM, $contentCodec, $preSaveValidators );
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
}
