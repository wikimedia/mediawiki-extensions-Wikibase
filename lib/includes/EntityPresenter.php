<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;

/**
 * TODO: name of the interface! EntityPrinter?
 */
interface EntityPresenter {

	/**
	 * TODO: what should be return type? Term (and subclasses, to be able to figure out if fallback was applied)?
	 * Or why not something else? E.g. what about "complex labels" like "German, noun" for lexeme?
	 *
	 * @param EntityId $id
	 *
	 * @return string
	 */
	public function getDisplayLabel( EntityId $id );

	/**
	 * TODO: what should be return type? Term (and subclasses, to be able to figure out if fallback was applied)?
	 * Or why not something else? E.g. what about "complex labels" like "German, noun" for lexeme?
	 *
	 * @param EntityId $id
	 *
	 * @return string
	 */
	public function getSecondaryLabel( EntityId $id );

}
