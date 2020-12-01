<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Result of changing a language-bound part of the entity
 *
 * Examples are terms of an item or property
 * @license GPL-2.0-or-later
 */
interface LanguageBoundChangeOpResult extends ChangeOpResult {

	/**
	 * The language code of the entity part that changed
	 * @return string
	 */
	public function getLanguageCode();

}
