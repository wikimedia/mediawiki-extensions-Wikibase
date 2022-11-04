<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatements;

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

		$this->assertSame( GetItemStatementsValidator::SOURCE_ITEM_ID, $error->getSource() );
		$this->assertSame( $invalidId, $error->getContext()[ItemIdValidator::ERROR_CONTEXT_VALUE] );
	}

	public function testWithValidId(): void {
		$this->assertNull(
			$this->newStatementsValidator()
				->validate( new GetItemStatementsRequest( 'Q321' ) )
		);
	}

	private function newStatementsValidator(): GetItemStatementsValidator {
		return ( new GetItemStatementsValidator( new ItemIdValidator() ) );
	}

}
