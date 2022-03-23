<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\ErrorResult;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResult;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenterTest extends TestCase {

	public function testGetJson(): void {
		$error = new GetItemErrorResult( ErrorResult::ITEM_NOT_FOUND, 'Could not find an item with the ID Q123' );

		$presenter = new ErrorJsonPresenter();

		$this->assertJsonStringEqualsJsonString(
			'{"code":"' . ErrorResult::ITEM_NOT_FOUND . '","message":"Could not find an item with the ID Q123"}',
			$presenter->getJson( $error )
		);
	}

}
