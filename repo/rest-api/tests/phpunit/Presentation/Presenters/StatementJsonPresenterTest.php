<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Serializers\StatementSerializer;
use Wikibase\Repo\RestApi\Presentation\Presenters\StatementJsonPresenter;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\StatementJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementJsonPresenterTest extends TestCase {

	public function testGetJsonForSuccess(): void {
		$statement = $this->createStub( Statement::class );
		$statementSerialization = [ 'id' => 'Q1$943B784F-FFBC-43A9-B7AA-79C3546AA0EF' ];

		$statementSerializer = $this->createMock( StatementSerializer::class );
		$statementSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $statement )
			->willReturn( $statementSerialization );

		$presenter = new StatementJsonPresenter( $statementSerializer );

		$this->assertJsonStringEqualsJsonString(
			json_encode( $statementSerialization ),
			$presenter->getJson( $statement )
		);
	}

}
