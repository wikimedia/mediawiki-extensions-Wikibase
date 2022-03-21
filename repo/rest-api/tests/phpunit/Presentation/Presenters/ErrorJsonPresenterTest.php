<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\ErrorReporter;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenterTest extends TestCase {

	public function testGetJsonItemForFailure(): void {
		$error = new ErrorReporter( 'item-not-found', 'Could not find an item with the ID Q123' );

		$presenter = new ErrorJsonPresenter();

		$this->assertEquals(
			'{"code":"item-not-found","message":"Could not find an item with the ID Q123"}',
			$presenter->getErrorJson( $error )
		);
	}

}
