<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Content\Content;
use MediaWiki\Context\IContextSource;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikibase\DataAccess\Hooks\GetEntityContentModelForTitleHook;
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
	WikibaseContentModelMappingHook,
	WikibaseEditFilterMergedContentHook,
	WikibaseRepoDataTypesHook,
	WikibaseRepoEntityNamespacesHook,
	WikibaseRepoEntitySearchHelperCallbacksHook,
	WikibaseRepoEntityTypesHook,
	WikibaseRepoOnParserOutputUpdaterConstructionHook,
	WikibaseRepoSearchableEntityScopesMessagesHook,
	WikibaseRepoSearchableEntityScopesHook,
	WikibaseTextForSearchIndexHook
{

	private HookContainer $hookContainer;

	public function __construct( HookContainer $container ) {
		$this->hookContainer = $container;
	}

	/** @inheritDoc */
	public function onEditFilterMergedContent(
		IContextSource $context,
		Content $content,
		Status $status,
		$summary,
		User $user,
		$minoredit,
		string $slotRole = SlotRecord::MAIN
	) {
		return $this->hookContainer->run(
			'EditFilterMergedContent',
			[ $context, $content, &$status, $summary, $user, $minoredit, $slotRole ]
		);
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

	public function onWikibaseContentModelMapping( array &$map ): void {
		/**
		 * Warning: This hook runs as part of an early initialization service
		 * (WikibaseRepo.ContentModelMappings, see service wiring and the warning there).
		 */
		$this->hookContainer->run(
			'WikibaseContentModelMapping',
			[ &$map ],
			[
				'abortable' => false,
				'noServices' => true, // early initialization
			]
		);
	}

	public function onWikibaseRepoDataTypes( array &$dataTypeDefinitions ): void {
		$this->hookContainer->run( 'WikibaseRepoDataTypes',
			[ &$dataTypeDefinitions ],
			[ 'abortable' => false ]
		);
	}

	public function onWikibaseRepoEntitySearchHelperCallbacks( array &$callbacks ): void {
		$this->hookContainer->run( 'WikibaseRepoEntitySearchHelperCallbacks',
			[ &$callbacks ],
			[ 'abortable' => false ]
		);
	}

	public function onWikibaseRepoEntityTypes( array &$entityTypeDefinitions ): void {
		/**
		 * Warning: This hook runs as part of an early initialization service
		 * (WikibaseRepo.EntityTypeDefinitions, see service wiring and the warning there).
		 */
		$this->hookContainer->run(
			'WikibaseRepoEntityTypes',
			[ &$entityTypeDefinitions ],
			[
				'abortable' => false,
				'noServices' => true, // early initialization
			]
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
			[ &$searchableEntityScopes ],
			[ 'abortable' => false ]
		);
	}

	/** @inheritDoc */
	public function onWikibaseTextForSearchIndex( EntityContent $entityContent, string &$text ) {
		return $this->hookContainer->run(
			'WikibaseTextForSearchIndex',
			[ $entityContent, &$text ]
		);
	}

	/** @inheritDoc */
	public function onWikibaseRepoEntityNamespaces( array &$entityNamespaces ): void {
		$this->hookContainer->run(
			'WikibaseRepoEntityNamespaces',
			[ &$entityNamespaces ],
			[ 'abortable' => false ]
		);
	}
}
