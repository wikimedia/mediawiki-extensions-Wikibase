<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenterTest extends TestCase {

	public function testGetJson(): void {
		$error = new GetItemErrorResponse( ErrorResponse::ITEM_NOT_FOUND, 'Could not find an item with the ID Q123' );

		$presenter = new ErrorJsonPresenter();

		$this->assertJsonStringEqualsJsonString(
			'{"code":"' . ErrorResponse::ITEM_NOT_FOUND . '","message":"Could not find an item with the ID Q123"}',
			$presenter->getJson( $error )
		);
	}

}
