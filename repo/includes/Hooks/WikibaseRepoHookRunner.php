<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\HookContainer\HookContainer;
use Wikibase\Repo\Content\EntityContent;

/**
 * Partial implementation of a hook runner for WikibaseRepo.
 * Full implementation will be addressed in T338452
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoHookRunner implements
	WikibaseRepoSearchableEntityScopesMessagesHook,
	WikibaseRepoSearchableEntityScopesHook,
	WikibaseTextForSearchIndexHook
{

	private HookContainer $hookContainer;

	public function __construct( HookContainer $container ) {
		$this->hookContainer = $container;
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
