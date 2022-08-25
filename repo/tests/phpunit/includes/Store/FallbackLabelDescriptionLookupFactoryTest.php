<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\WikibaseRepo;

/**
 * Integration test checking that the (Lib) FallbackLabelDescriptionLookupFactory,
 * as used in WikibaseRepo’s service wiring, returns a lookup
 * that applies language fallbacks and resolves redirects.
 *
 * @covers \Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory
 * @covers \Wikibase\Lib\Store\CachingFallbackLabelDescriptionLookup
 * @covers \Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup
 * @covers \Wikibase\Lib\Store\DispatchingFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class FallbackLabelDescriptionLookupFactoryTest extends MediaWikiIntegrationTestCase {

	public function testFallbackAndRedirect(): void {
		$item = NewItem::withId( 'Q1' )
			->andLabel( 'de', 'Label' )
			->andLabel( 'en', 'label' )
			->build();
		$redirect = new EntityRedirect( new ItemId( 'Q2' ), $item->getId() );
		$services = $this->getServiceContainer();
		$store = WikibaseRepo::getEntityStore( $services );
		$store->saveEntity(
			$item,
			'test case item',
			$this->getTestUser()->getUser()
		);
		$store->saveRedirect(
			$redirect,
			'test case redirect',
			$this->getTestUser()->getUser()
		);
		$deAtLanguage = $services->getLanguageFactory()->getLanguage( 'de-at' );
		$fallbackLabelDescriptionLookup = WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
			->newLabelDescriptionLookup( $deAtLanguage );

		$label = $fallbackLabelDescriptionLookup->getLabel( $redirect->getEntityId() );

		$this->assertNotNull( $label );
		$this->assertSame( 'Label', $label->getText() );
		$this->assertSame( 'de', $label->getActualLanguageCode() );
	}

}
