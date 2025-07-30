<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use MediaWiki\ChangeTags\ChangeTagsStore;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\ChangeTagsStoreTagsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\ChangeTagsStoreTagsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ChangeTagsStoreTagsRetrieverTest extends TestCase {

	public function testGetAllowedTags(): void {
		$changeTagsStore = $this->createStub( ChangeTagsStore::class );
		$changeTagsStore->method( 'listExplicitlyDefinedTags' )->willReturn( [ 'foo' ] );

		$tagsRetriever = new ChangeTagsStoreTagsRetriever( $changeTagsStore );
		$response = $tagsRetriever->getAllowedTags();
		$this->assertSame( [ 'foo' ], $response );
	}
}
