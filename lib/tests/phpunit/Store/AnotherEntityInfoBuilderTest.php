<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\Store\AnotherEntityInfoBuilder;
use Wikibase\Lib\Store\CachingLabelDescriptionLookupForBatch;
use Wikibase\Lib\Store\EntityRetrievingLabelDescriptionLookupForBatch;
use Wikibase\Lib\Store\EntityRevisionCache;

/**
 * @license GPL-2.0-or-later
 *
 * @covers \Wikibase\Lib\Store\AnotherEntityInfoBuilder
 */
class AnotherEntityInfoBuilderTest extends EntityInfoBuilderTestCase {

	protected function newEntityInfoBuilder() {
		$entityLookup = new InMemoryEntityLookup();
		foreach ( $this->getKnownEntities() as $entity ) {
			$entityLookup->addEntity( $entity );
		}
		foreach ( $this->getKnownRedirects() as $fromId => $toId ) {
			$entityLookup->addException( new UnresolvedEntityRedirectException( new ItemId( $fromId ), $toId ) );
		}

		return new AnotherEntityInfoBuilder(
			new CachingLabelDescriptionLookupForBatch(
				new EntityRevisionCache( new \HashBagOStuff() ),
				new SimpleCacheWithBagOStuff( new \HashBagOStuff(), 'test' ),
				new EntityRetrievingLabelDescriptionLookupForBatch(
					new EntityRetrievingTermLookup(
						new RedirectResolvingEntityLookup( $entityLookup )
					)
				)
			)
		);
	}

}
