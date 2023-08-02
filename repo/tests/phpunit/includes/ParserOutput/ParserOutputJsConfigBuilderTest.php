<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWikiIntegrationTestCase;
use ParserOutput;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder;

/**
 * @covers \Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserOutputJsConfigBuilderTest extends MediaWikiIntegrationTestCase {

	public function testBuildConfigItem() {
		$item = new Item( new ItemId( 'Q5881' ) );
		$this->addLabels( $item );

		$parserOutput = $this->createMock( ParserOutput::class );
		$this->assertWbEntityId( 'Q5881', $parserOutput, $sawOnce );

		$configBuilder = new ParserOutputJsConfigBuilder();
		$configBuilder->build( $item, $parserOutput );
		$this->assertTrue( $sawOnce );
	}

	public function testBuildConfigProperty() {
		$property = new Property( new NumericPropertyId( 'P330' ), null, 'string' );
		$this->addLabels( $property );

		$parserOutput = $this->createMock( ParserOutput::class );
		$this->assertWbEntityId( 'P330', $parserOutput, $sawOnce );

		$configBuilder = new ParserOutputJsConfigBuilder();
		$configBuilder->build( $property, $parserOutput );
		$this->assertTrue( $sawOnce );
	}

	public function assertWbEntityId( $expectedId, $parserOutputMock, &$sawOnce ) {
		$sawOnce = false;
		$parserOutputMock
			->expects( $this->atLeastOnce() )
			->method( 'setJsConfigVar' )
			->with( $this->anything(), $this->anything() )
			->willReturnCallback( function( $name, $value ) use ( &$sawOnce, $expectedId ) {
				if ( $name === 'wbEntityId' && $value === $expectedId ) {
					$sawOnce = true;
				}
			} );
	}

	private function addLabels( LabelsProvider $entity ) {
		$termList = $entity->getLabels();
		$termList->setTextForLanguage( 'en', 'Cake' );
		$termList->setTextForLanguage( 'de', 'Kuchen' );
	}

}
