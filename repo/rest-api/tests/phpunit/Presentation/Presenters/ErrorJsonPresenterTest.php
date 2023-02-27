<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenterTest extends TestCase {

	public function testGetJson_withoutContext(): void {
		$error = new ErrorResponse( ErrorResponse::ITEM_NOT_FOUND, 'Could not find an item with the ID Q123' );

		$presenter = new ErrorJsonPresenter();

		$this->assertJsonStringEqualsJsonString(
			'{"code":"' . ErrorResponse::ITEM_NOT_FOUND . '","message":"Could not find an item with the ID Q123"}',
			$presenter->getJson( $error )
		);
	}

	public function testGetJson_withContext(): void {
		$error = new ErrorResponse(
			'test-error-code',
			'Test error message',
			[ 'testing' => 'with', 'context' => 42 ]
		);

		$presenter = new ErrorJsonPresenter();

		$this->assertJsonStringEqualsJsonString(
			'{"code":"test-error-code","message":"Test error message","context":{"testing":"with","context":42}}',
			$presenter->getJson( $error )
		);
	}

}
