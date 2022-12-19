<?php

namespace Wikibase\Repo\Tests\Hooks;

use IContextSource;
use OutputPage;
use RequestContext;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;

/**
 * @covers \Wikibase\Repo\Hooks\OutputPageEntityIdReader
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageEntityIdReaderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider getEntityIdFromOutputPageProvider
	 */
	public function testGetEntityIdFromOutputPage( $expected, OutputPage $out, bool $hasEntityView ) {
		$entityViewChecker = $this->createMock( OutputPageEntityViewChecker::class );
		$entityViewChecker->expects( $this->once() )
			->method( 'hasEntityView' )
			->willReturn( $hasEntityView );

		$outputPageEntityIdReader = new OutputPageEntityIdReader(
			$entityViewChecker,
			new ItemIdParser()
		);

		$this->assertEquals(
			$expected,
			$outputPageEntityIdReader->getEntityIdFromOutputPage( $out )
		);
	}

	public function getEntityIdFromOutputPageProvider() {
		yield 'Entity id set' => [
			new ItemId( 'Q42' ),
			$this->newOutputPageWithJsConfigVars( [ 'wbEntityId' => 'Q42' ] ),
			true,
		];
		yield 'page with entity view, but no entity id set' => [
			null,
			$this->newOutputPageWithJsConfigVars( [] ),
			true,
		];

		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->never() )->method( $this->anything() );
		yield 'no entity view page, should abort early' => [
			null,
			$out,
			false,
		];
	}

	private function newOutputPageWithJsConfigVars( array $config ) {
		$context = $this->createMock( IContextSource::class );
		$context->method( 'getRequest' )
			->willReturn( RequestContext::getMain()->getRequest() );
		$context->method( 'getConfig' )
			->willReturn( RequestContext::getMain()->getConfig() );

		$out = new OutputPage( $context );
		$out->addJsConfigVars( $config );

		return $out;
	}

}
