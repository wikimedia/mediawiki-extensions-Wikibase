<?php


namespace Wikibase\Repo\ChangeOp;

/**
 * Result of changing a language-bound part of the entity
 *
 * Examples are terms of an item or property
 */
interface LanguageBoundChangeOpResult extends ChangeOpResult {

	/**
	 * The language code of the entity part that changed
	 * @return string
	 */
	public function getLanguageCode();

}
