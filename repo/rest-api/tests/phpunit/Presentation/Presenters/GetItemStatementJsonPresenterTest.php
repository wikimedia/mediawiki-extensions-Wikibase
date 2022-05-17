<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementSuccessResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementJsonPresenterTest extends TestCase {

	public function testGetJsonForSuccess(): void {
		$statement = $this->createStub( Statement::class );
		$statementSerialization = [ 'id' => 'Q1$943B784F-FFBC-43A9-B7AA-79C3546AA0EF' ];

		$statementSerializer = $this->createMock( StatementSerializer::class );
		$statementSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $statement )
			->willReturn( $statementSerialization );

		$presenter = new GetItemStatementJsonPresenter( $statementSerializer );

		$this->assertJsonStringEqualsJsonString(
			json_encode( $statementSerialization ),
			$presenter->getJson(
				new GetItemStatementSuccessResponse( $statement, '20220307180000', 321 )
			)
		);
	}

}
