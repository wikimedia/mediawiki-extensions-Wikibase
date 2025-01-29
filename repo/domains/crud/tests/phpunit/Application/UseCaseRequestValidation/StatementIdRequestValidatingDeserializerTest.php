<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequestValidatingDeserializer
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

	public static function statementIdProvider(): Generator {
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
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'statement_id'", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'statement_id' ], $e->getErrorContext() );
		}
	}

	public static function invalidStatementIdProvider(): Generator {
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
