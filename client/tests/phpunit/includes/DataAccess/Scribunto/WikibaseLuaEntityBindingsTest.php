<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Language;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\WikibaseLuaEntityBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindingsTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return WikibaseLuaEntityBindings
	 */
	private function getWikibaseLuaEntityBindings() {
		$entityStatementsRenderer = $this->getMockBuilder( StatementTransclusionInteractor::class )
			->disableOriginalConstructor()
			->getMock();

		$entityStatementsRenderer->expects( $this->any() )
				->method( 'render' )
				->with( new ItemId( 'Q12' ), 'some label', array( Statement::RANK_DEPRECATED ) )
				->will( $this->returnValue( 'Kittens > Cats' ) );

		return new WikibaseLuaEntityBindings(
			$entityStatementsRenderer,
			new BasicEntityIdParser(),
			Language::factory( 'es' ),
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

	public function testGetLanguageCode() {
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings();

		$this->assertEquals( 'es', $wikibaseLuaEntityBindings->getLanguageCode() );
	}

}
