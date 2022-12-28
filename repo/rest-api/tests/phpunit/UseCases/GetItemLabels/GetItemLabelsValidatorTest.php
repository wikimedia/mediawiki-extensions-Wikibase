<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelsValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		$error = $this->newLabelsValidator()
			->validate( new GetItemLabelsRequest( $invalidId ) );

		$this->assertSame( ItemIdValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( $invalidId, $error->getContext()[ItemIdValidator::CONTEXT_VALUE] );
	}

	public function testWithValidId(): void {
		$this->assertNull(
			$this->newLabelsValidator()
				->validate( new GetItemLabelsRequest( 'Q321' ) )
		);
	}

	private function newLabelsValidator(): GetItemLabelsValidator {
		return ( new GetItemLabelsValidator( new ItemIdValidator() ) );
	}

}
