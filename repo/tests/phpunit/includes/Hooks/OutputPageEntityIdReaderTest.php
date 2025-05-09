<?php

namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Output\OutputPage;
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
	public function testGetEntityIdFromOutputPage( $expected, callable $outputPageFactory, bool $hasEntityView ) {
		$out = $outputPageFactory( $this );

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

	public static function getEntityIdFromOutputPageProvider(): iterable {
		yield 'Entity id set' => [
			new ItemId( 'Q42' ),
			fn ( self $self ) => $self->newOutputPageWithJsConfigVars( [ 'wbEntityId' => 'Q42' ] ),
			true,
		];
		yield 'page with entity view, but no entity id set' => [
			null,
			fn ( self $self ) => $self->newOutputPageWithJsConfigVars( [] ),
			true,
		];

		yield 'no entity view page, should abort early' => [
			null,
			fn ( self $self ) => $self->newOutputPageMockExpectingNothing(),
			false,
		];
	}

	private function newOutputPageMockExpectingNothing(): OutputPage {
		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->never() )->method( $this->anything() );

		return $out;
	}

	private function newOutputPageWithJsConfigVars( array $config ): OutputPage {
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
