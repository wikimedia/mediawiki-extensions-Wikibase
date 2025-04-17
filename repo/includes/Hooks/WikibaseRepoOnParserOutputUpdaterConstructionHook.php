<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Wikibase\Repo\ParserOutput\EntityParserOutputUpdater;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoOnParserOutputUpdaterConstruction" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoOnParserOutputUpdaterConstructionHook {

	/**
	 * Allows extensions to register extra EntityParserOutputUpdater implementations.
	 *
	 * @param StatementDataUpdater $statementUpdater
	 * @param EntityParserOutputUpdater[] &$entityUpdaters
	 */
	public function onWikibaseRepoOnParserOutputUpdaterConstruction(
		StatementDataUpdater $statementUpdater,
		array &$entityUpdaters
	): void;

}
