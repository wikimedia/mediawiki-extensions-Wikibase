<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\Controllers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\FallbackEntitySearchHelperController;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\WbSearchEntitiesRequest;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\Controllers\FallbackEntitySearchHelperController
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FallbackEntitySearchHelperControllerTest extends TestCase {

	public function testSearchDelegatesToHelperWithCorrectEntityType(): void {
		$searchHelper = $this->createMock( EntitySearchHelper::class );
		$searchHelper->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( 'foo', 'en', 'item', 5, true, 'default' )
			->willReturn( [] );

		$controller = new FallbackEntitySearchHelperController(
			'item',
			$searchHelper,
			$this->createStub( EntitySourceLookup::class )
		);

		$controller->search( new WbSearchEntitiesRequest( 'foo', 'en', 'en', 5, true, 'default', null ) );
	}

}
