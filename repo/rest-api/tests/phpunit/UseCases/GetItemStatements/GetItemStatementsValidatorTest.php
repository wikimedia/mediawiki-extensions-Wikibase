<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatements;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		$error = $this->newStatementsValidator()
			->validate( new GetItemStatementsRequest( $invalidId ) );

		$this->assertSame( ItemIdValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( $invalidId, $error->getContext()[ItemIdValidator::CONTEXT_VALUE] );
	}

	public function testWithInvalidPropertyFilter(): void {
		$invalidPropertyId = 'X123';

		$error = $this->newStatementsValidator()
			->validate( new GetItemStatementsRequest( 'Q123', $invalidPropertyId ) );

		$this->assertSame( GetItemStatementsValidator::CODE_INVALID_PROPERTY_ID, $error->getCode() );
	}

	/**
	 * @dataProvider validRequestProvider
	 */
	public function testWithValidRequest( GetItemStatementsRequest $request ): void {
		$this->assertNull(
			$this->newStatementsValidator()
				->validate( $request )
		);
	}

	public function validRequestProvider(): Generator {
		yield [ new GetItemStatementsRequest( 'Q321' ) ];
		yield [ new GetItemStatementsRequest( 'Q321', 'P123' ) ];
	}

	private function newStatementsValidator(): GetItemStatementsValidator {
		return ( new GetItemStatementsValidator( new ItemIdValidator() ) );
	}

}
