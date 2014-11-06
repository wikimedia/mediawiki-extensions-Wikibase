<?php

namespace Wikibase\Test;

use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\CachingLanguageFallbackLabelLookup;

class CachingLanguageFallbackLabelLookupTest extends \MediaWikiTestCase {

	public function testGetLabel() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) );

		$entityLookup = $this->getEntityLookup();
		$labelLookup = new CachingLanguageFallbackLabelLookup( $entityLookup, $fallbackChain );

		$label = $labelLookup->getLabel( new ItemId( 'Q118' ) );
		$this->assertEquals( '测试', $label );
	}

	public function testGetLabel_notFound() {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $languageFallbackChainFactory->newFromLanguage( Language::factory( 'zh' ) );

		$entityLookup = $this->getEntityLookup();
		$labelLookup = new CachingLanguageFallbackLabelLookup( $entityLookup, $fallbackChain );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelLookup->getLabel( new ItemId( 'Q120' ) );
	}

	private function getEntityLookup() {
		$mockRepo = new MockRepository();

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q116' ) );
		$item->setLabel( 'en', 'New York City' );
		$item->setLabel( 'es', 'Nueva York' );
		$item->setDescription( 'en', 'Big Apple' );

		$mockRepo->putEntity( $item );

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q117' ) );
		$item->setLabel( 'en', 'Berlin' );

		$mockRepo->putEntity( $item );

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q118' ) );
		$item->setLabel( 'zh-cn', '测试' );

		$mockRepo->putEntity( $item );

		return $mockRepo;
	}

}
