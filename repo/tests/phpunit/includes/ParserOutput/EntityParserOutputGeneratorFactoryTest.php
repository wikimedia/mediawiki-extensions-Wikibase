<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactoryTest extends \MediaWikiTestCase {

	public function testGetEntityParserOutputGenerator() {
		$parserOutputGeneratorFactory = $this->getEntityParserOutputGeneratorFactory();

		$instance = $parserOutputGeneratorFactory->getEntityParserOutputGenerator(
			'en', true
		);

		$this->assertInstanceOf( EntityParserOutputGenerator::class, $instance );
	}

	private function getEntityParserOutputGeneratorFactory() {
		return new EntityParserOutputGeneratorFactory(
			$this->getMockBuilder( DispatchingEntityViewFactory::class )
				->disableOriginalConstructor()->getMock(),
			$this->getMock( EntityInfoBuilderFactory::class ),
			$this->getMock( EntityTitleLookup::class ),
			new LanguageFallbackChainFactory(),
			$this->getMockBuilder( TemplateFactory::class )
				->disableOriginalConstructor()->getMock(),
			$this->getMock( EntityDataFormatProvider::class ),
			$this->getMock( PropertyDataTypeLookup::class ),
			new BasicEntityIdParser(),
			$this->getMock( Serializer::class )
		);
	}

}
