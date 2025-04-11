<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\Title;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * Partial implementation of a hook runner for WikibaseRepo.
 * Full implementation will be addressed in T338452
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoHookRunner implements
	GetEntityByLinkedTitleLookupHook,
	GetEntityContentModelForTitleHook,
	WikibaseChangeNotificationHook,
	WikibaseRepoOnParserOutputUpdaterConstructionHook,
	WikibaseRepoSearchableEntityScopesMessagesHook,
	WikibaseRepoSearchableEntityScopesHook,
	WikibaseTextForSearchIndexHook
{

	private HookContainer $hookContainer;

	public function __construct( HookContainer $container ) {
		$this->hookContainer = $container;
	}

	public function onGetEntityByLinkedTitleLookup( EntityByLinkedTitleLookup &$lookup ): void {
		$this->hookContainer->run(
			'GetEntityByLinkedTitleLookup',
			[ &$lookup ],
			[ 'abortable' => false ]
		);
	}

	public function onGetEntityContentModelForTitle( Title $title, string &$contentModel ): void {
		$this->hookContainer->run(
			'GetEntityContentModelForTitle',
			[ $title, &$contentModel ],
			[ 'abortable' => false ]
		);
	}

	public function onWikibaseChangeNotification( Change $change ): void {
		$this->hookContainer->run(
			'WikibaseChangeNotification',
			[ $change ],
			[ 'abortable' => false ]
		);
	}

	public function onWikibaseRepoOnParserOutputUpdaterConstruction(
		StatementDataUpdater $statementUpdater,
		array &$entityUpdaters
	): void {
		$this->hookContainer->run(
			'WikibaseRepoOnParserOutputUpdaterConstruction',
			[ $statementUpdater, &$entityUpdaters ],
			[ 'abortable' => false ]
		);
	}

	/** @inheritDoc */
	public function onWikibaseRepoSearchableEntityScopesMessages( array &$messages ): void {
		$this->hookContainer->run(
			'WikibaseRepoSearchableEntityScopesMessages',
			[ &$messages ],
			[ 'abortable' => false ]
		);
	}

	/** @inheritDoc */
	public function onWikibaseRepoSearchableEntityScopes( array &$searchableEntityScopes ): void {
		$this->hookContainer->run(
			'WikibaseRepoSearchableEntityScopes',
			[ &$searchableEntityScopes ]
		);
	}

	/** @inheritDoc */
	public function onWikibaseTextForSearchIndex( EntityContent $entityContent, string &$text ) {
		return $this->hookContainer->run(
			'WikibaseTextForSearchIndex',
			[ $entityContent, &$text ]
		);
	}

}
