<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResult;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenterTest extends TestCase {

	public function testGetJsonItemForFailure(): void {
		$error = new GetItemErrorResult( 'item-not-found', 'Could not find an item with the ID Q123' );

		$presenter = new ErrorJsonPresenter();

		$this->assertEquals(
			'{"code":"item-not-found","message":"Could not find an item with the ID Q123"}',
			$presenter->getErrorJson( $error )
		);
	}

}
