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
		$plainTextTransclusionInteractor = $this->getMockBuilder( StatementTransclusionInteractor::class )
			->disableOriginalConstructor()
			->getMock();

		$plainTextTransclusionInteractor->expects( $this->any() )
				->method( 'render' )
				->with( new ItemId( 'Q12' ), 'some label', [ Statement::RANK_DEPRECATED ] )
				->will( $this->returnValue( 'Kittens > Cats' ) );

		$richWikitextTransclusionInteractor = $this->getMockBuilder( StatementTransclusionInteractor::class )
			->disableOriginalConstructor()
			->getMock();

		$richWikitextTransclusionInteractor->expects( $this->any() )
				->method( 'render' )
				->with( new ItemId( 'Q12' ), 'some label', [ Statement::RANK_DEPRECATED ] )
				->will( $this->returnValue( '<span>Kittens > Cats</span>' ) );

		return new WikibaseLuaEntityBindings(
			$plainTextTransclusionInteractor,
			$richWikitextTransclusionInteractor,
			new BasicEntityIdParser(),
			Language::factory( 'es' ),
			'enwiki'
		);
	}

	public function testFormatPropertyValues() {
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings();

		$this->assertSame(
			'Kittens > Cats',
			$wikibaseLuaEntityBindings->formatPropertyValues(
				'Q12',
				'some label',
				[ Statement::RANK_DEPRECATED ]
			)
		);
	}

	public function testFormatStatements() {
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings();

		$this->assertSame(
			'<span>Kittens > Cats</span>',
			$wikibaseLuaEntityBindings->formatStatements(
				'Q12',
				'some label',
				[ Statement::RANK_DEPRECATED ]
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
