<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\ChangeTags\ChangeTagsStore;
use MediaWiki\Rest\ConditionalHeaderUtil;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * Utilities for testing REST handlers.
 *
 * @license GPL-2.0-or-later
 */
trait RestHandlerTestUtilsTrait {

	/**
	 * Overrides core's ChangeTagsStore service with one that doesn't need the database.
	 */
	private function setMockChangeTagsStore(): void {
		$changeTagsStore = $this->createMock( ChangeTagsStore::class );
		$changeTagsStore->method( 'listExplicitlyDefinedTags' )->willReturn( [] );
		$this->setService( 'ChangeTagsStore', $changeTagsStore );
	}

	/**
	 * Overrides the PreconditionMiddlewareFactory service with one that doesn't need the database.
	 */
	private function setMockPreconditionMiddlewareFactory(): void {
		$entityRevLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevLookup->method( 'getLatestRevisionId' )->willReturn( LatestRevisionIdResult::nonexistentEntity() );
		$preconditionMiddlewareFactory = new PreconditionMiddlewareFactory(
			$entityRevLookup,
			WikibaseRepo::getEntityIdParser(),
			new ConditionalHeaderUtil()
		);
		$this->setService( 'WbRestApi.PreconditionMiddlewareFactory', $preconditionMiddlewareFactory );
	}
}
