<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementValidatorTest extends TestCase {

	/**
	 * @dataProvider invalidStatementIdDataProvider
	 */
	public function testWithInvalidStatementId( string $statementId ): void {
		try {
			$this->newStatementValidator()->assertValidRequest(
				new GetItemStatementRequest( $statementId )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( StatementIdValidator::CODE_INVALID, $e->getErrorCode() );
			$this->assertSame(
				'Not a valid statement ID: ' . $statementId,
				$e->getErrorMessage()
			);
		}
	}

	public function invalidStatementIdDataProvider(): Generator {
		yield 'invalid format' => [ 'not-a-valid-statement-id' ];
		yield 'invalid ItemId' => [ 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
		yield 'invalid UUID part' => [ 'Q123$INVALID-UUID-PART' ];
		yield 'statement not on an item' => [ 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ];
	}

	public function testWithInvalidItemId(): void {
		$itemId = 'X123';
		try {
			$this->newStatementValidator()->assertValidRequest(
				new GetItemStatementRequest(
					'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					$itemId
				)
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ItemIdValidator::CODE_INVALID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: ' . $itemId, $e->getErrorMessage() );
		}
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidStatementId(): void {
		$this->newStatementValidator()->assertValidRequest(
			new GetItemStatementRequest( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
		);
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWithValidStatementIdAndItemId(): void {
		$this->newStatementValidator()->assertValidRequest(
			new GetItemStatementRequest(
				'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'Q123'
			)
		);
	}

	private function newStatementValidator(): GetItemstatementValidator {
		return new GetItemStatementValidator(
			new StatementIdValidator( new ItemIdParser() ),
			new ItemIdValidator()
		);
	}

}
