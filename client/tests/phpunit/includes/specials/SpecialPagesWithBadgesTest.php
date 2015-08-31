<?php


namespace Wikibase\Client\Tests\Specials;

use Wikibase\Client\Specials\SpecialPagesWithBadges;
use Wikibase\Test\SpecialPageTestBase;

/**
 * @covers Wikibase\Client\Specials\SpecialPagesWithBadges
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialPagesWithBadgesTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		$specialPage = new SpecialPagesWithBadges();
		$specialPage->initSettings( array( 'Q123', 'Q456' ), 'enwiki' );

		return $specialPage;
	}

	public function testExecuteWithoutAnyParams() {
		list( $result, ) = $this->executeSpecialPage( '' );

		$this->assertContains( '<select name="badge"', $result );
		$this->assertContains( '<option value="Q123"', $result );
		$this->assertContains( '<option value="Q456"', $result );
	}

	public function testExecuteWithValidParam() {
		list( $result, ) = $this->executeSpecialPage( 'Q456' );

		$this->assertContains( '<option value="Q456" selected=""', $result );
	}

	public function testExecuteWithInvalidParam() {
		list( $result, ) = $this->executeSpecialPage( 'FooBar' );

		$this->assertContains( '<p class="error"', $result );
		$this->assertContains( 'FooBar is not a valid item id', $result );
	}

}
