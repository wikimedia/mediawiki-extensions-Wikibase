<?php

namespace Wikibase\Client\Tests\Scribunto;

use Wikibase\Client\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\Client\Scribunto\WikibaseLuaEntityBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindingsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return WikibaseLuaEntityBindings
	 */
	private function getWikibaseLuaEntityBindings() {
		$usageAccumulator = new HashUsageAccumulator();

		$entityStatementsRenderer = $this->getMockBuilder( 'Wikibase\DataAccess\StatementTransclusionInteractor' )
			->disableOriginalConstructor()
			->getMock();

		$entityStatementsRenderer->expects( $this->any() )
				->method( 'render' )
				->with( new ItemId( 'Q12' ), $usageAccumulator, 'some label', array( Statement::RANK_DEPRECATED ) )
				->will( $this->returnValue( 'Kittens > Cats' ) );

		return new WikibaseLuaEntityBindings(
			$entityStatementsRenderer,
			new BasicEntityIdParser(),
			$usageAccumulator,
			'enwiki'
		);
	}

	public function testFormatPropertyValues() {
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings();

		$this->assertEquals(
			'Kittens > Cats',
			$wikibaseLuaEntityBindings->formatPropertyValues(
				'Q12',
				'some label',
				array( Statement::RANK_DEPRECATED )
			)
		);
	}

	public function testGetGlobalSiteId() {
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings();

		$this->assertEquals( 'enwiki', $wikibaseLuaEntityBindings->getGlobalSiteId() );
	}

}
