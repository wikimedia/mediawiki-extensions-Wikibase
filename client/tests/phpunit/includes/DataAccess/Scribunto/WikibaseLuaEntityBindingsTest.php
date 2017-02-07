<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Language;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
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
	 * @param HashUsageAccumulator|null $usageAccumulator
	 *
	 * @return WikibaseLuaEntityBindings
	 */
	private function getWikibaseLuaEntityBindings( HashUsageAccumulator $usageAccumulator = null ) {
		$usageAccumulator = $usageAccumulator ?: new HashUsageAccumulator();

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
			$usageAccumulator,
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

	public function testAddStatementUsage() {
		$q2013 = new ItemId( 'Q2013' );
		$usageAccumulator = new HashUsageAccumulator();

		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings( $usageAccumulator );
		$wikibaseLuaEntityBindings->addStatementUsage( $q2013->getSerialization(), 'P1337', true );

		$this->assertCount( 1, $usageAccumulator->getUsages() );
		$this->assertEquals(
			[
				'Q2013#C.P1337' => new EntityUsage( $q2013, EntityUsage::STATEMENT_USAGE, 'P1337' )
			],
			$usageAccumulator->getUsages()
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
