<?php

namespace Wikibase\Repo\SeaHorse;

use \Wikibase\Repo\Content\EntityHolder;
use \Wikibase\Repo\Content\EntityContent;

/**
 * A Groom that can handles a horse (jkjk)
 *
 * This is the "Contenthandler" for the horse type.
 */
class Groom extends \Wikibase\Repo\Content\EntityHandler {

	// /**
	//  * @return (\Closure|class-string)[]
	//  */
	// public function getActionOverrides() {
	// 	return [
	// 		'history' => function ( Page $page, IContextSource $context ) {
	// 			return new HistoryEntityAction(
	// 				$page,
	// 				$context,
	// 				$this->entityIdLookup,
	// 				$this->labelLookupFactory->newLabelDescriptionLookup( $context->getLanguage() )
	// 			);
	// 		},
	// 		'view' => ViewEntityAction::class,
	// 		'edit' => EditEntityAction::class,
	// 		'submit' => SubmitEntityAction::class,
	// 	];
	// }

	/**
	 * @return string
	 */
	public function getSpecialPageForCreation() {
		return 'Foal';
	}

	/**
	 * Returns Item::ENTITY_TYPE
	 *
	 * @return string
	 */
	public function getEntityType() {
		return SeaHorseSaddle::ENTITY_TYPE;
	}

	/**
	 * @see EntityHandler::makeEmptyEntity()
	 *
	 * @return EntityDocument
	 */
	public function makeEmptyEntity() {
		return new SeaHorse();
	}

	/**
	 * @see EntityHandler::newEntityContent
	 *
	 * @param EntityHolder|null $entityHolder
	 *
	 * @return SeaHorseContent
	 */
	protected function newEntityContent( EntityHolder $entityHolder = null ) {
		return new SeaHorseContent( $entityHolder );
	}

	/**
	 * @see EntityContent::makeEntityId
	 *
	 * @param string $id
	 *
	 * @return EntityId
	 */
	public function makeEntityId( $id ) {
		return new SeaHorseId( $id );
	}
}
