<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWikiIntegrationTestCase;
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

		$configBuilder = new ParserOutputJsConfigBuilder();
		$configVars = $configBuilder->build( $item );

		$this->assertWbEntityId( 'Q5881', $configVars );
	}

	public function testBuildConfigProperty() {
		$property = new Property( new NumericPropertyId( 'P330' ), null, 'string' );
		$this->addLabels( $property );

		$configBuilder = new ParserOutputJsConfigBuilder();
		$configVars = $configBuilder->build( $property );

		$this->assertWbEntityId( 'P330', $configVars );
	}

	public function assertWbEntityId( $expectedId, array $configVars ) {
		$this->assertEquals(
			$expectedId,
			$configVars['wbEntityId'],
			'wbEntityId'
		);
	}

	private function addLabels( LabelsProvider $entity ) {
		$termList = $entity->getLabels();
		$termList->setTextForLanguage( 'en', 'Cake' );
		$termList->setTextForLanguage( 'de', 'Kuchen' );
	}

}
