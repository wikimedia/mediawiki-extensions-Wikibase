<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementsJsonPresenter;
use Wikibase\Repo\RestApi\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsSuccessResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementsJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsJsonPresenterTest extends TestCase {

	public function testGetJsonForSuccess(): void {
		$statements = $this->createStub( StatementList::class );
		$serialization = new ArrayObject(
			[
				'P31' => [ [ 'id' => 'Q1$943B784F-FFBC-43A9-B7AA-79C3546AA0EF' ] ]
			]
		);

		$serializer = $this->createMock( StatementListSerializer::class );
		$serializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $statements )
			->willReturn( $serialization );

		$presenter = new GetItemStatementsJsonPresenter( $serializer );

		$this->assertJsonStringEqualsJsonString(
			json_encode( $serialization ),
			$presenter->getJson(
				new GetItemStatementsSuccessResponse( $statements, '20220307180000', 321 )
			)
		);
	}

}
