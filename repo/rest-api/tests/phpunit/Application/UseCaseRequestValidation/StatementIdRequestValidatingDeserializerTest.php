<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementIdRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementIdRequestValidatingDeserializerTest extends TestCase {

	/**
	 * @dataProvider statementIdProvider
	 */
	public function testGivenValidRequest_returnsStatementId( StatementGuid $id ): void {
		$request = $this->createStub( StatementIdRequest::class );
		$request->method( 'getStatementId' )->willReturn( "$id" );

		$this->assertEquals(
			$id,
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function statementIdProvider(): Generator {
		yield [ new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ) ];
		yield [ new StatementGuid( new NumericPropertyId( 'P123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ) ];
	}

	/**
	 * @dataProvider invalidStatementIdProvider
	 */
	public function testGivenInvalidRequest_throws( string $id ): void {
		$request = $this->createStub( StatementIdRequest::class );
		$request->method( 'getStatementId' )->willReturn( $id );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertStringContainsString( $id, $e->getErrorMessage() );
		}
	}

	public function invalidStatementIdProvider(): Generator {
		yield 'invalid id format' => [ 'Q123$invalid' ];
		yield 'unsupported entity type' => [ 'L123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
	}

	private function newValidatingDeserializer(): StatementIdRequestValidatingDeserializer {
		$entityIdParser = new BasicEntityIdParser();

		return new StatementIdRequestValidatingDeserializer(
			new StatementIdValidator( $entityIdParser ),
			new StatementGuidParser( $entityIdParser )
		);
	}

}
