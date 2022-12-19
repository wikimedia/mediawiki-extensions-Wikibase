<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use MediaWiki\MediaWikiServices;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseLuaEntityBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindingsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param HashUsageAccumulator|null $usageAccumulator
	 *
	 * @return WikibaseLuaEntityBindings
	 */
	private function getWikibaseLuaEntityBindings( HashUsageAccumulator $usageAccumulator = null ) {
		$plainTextTransclusionInteractor = $this->createMock( StatementTransclusionInteractor::class );

		$plainTextTransclusionInteractor->method( 'render' )
				->with( new ItemId( 'Q12' ), 'some label', [ Statement::RANK_DEPRECATED ] )
				->willReturn( 'Kittens > Cats' );

		$richWikitextTransclusionInteractor = $this->createMock( StatementTransclusionInteractor::class );

		$richWikitextTransclusionInteractor->method( 'render' )
				->with( new ItemId( 'Q12' ), 'some label', [ Statement::RANK_DEPRECATED ] )
				->willReturn( '<span>Kittens > Cats</span>' );

		return new WikibaseLuaEntityBindings(
			$plainTextTransclusionInteractor,
			$richWikitextTransclusionInteractor,
			new BasicEntityIdParser(),
			WikibaseClient::getTermsLanguages(),
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'es' ),
			$usageAccumulator ?: new HashUsageAccumulator(),
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
		$wikibaseLuaEntityBindings->addStatementUsage( $q2013->getSerialization(), 'P1337' );

		$this->assertCount( 1, $usageAccumulator->getUsages() );
		$this->assertEquals(
			[
				'Q2013#C.P1337' => new EntityUsage( $q2013, EntityUsage::STATEMENT_USAGE, 'P1337' ),
			],
			$usageAccumulator->getUsages()
		);
	}

}
