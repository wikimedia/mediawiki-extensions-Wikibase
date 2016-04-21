<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;
use Wikibase\Test\EntityInfoBuilderTest;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Lib\Store\GenericEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class GenericEntityInfoBuilderTest extends EntityInfoBuilderTest {

	/**
	 * @param array $ids
	 *
	 * @return GenericEntityInfoBuilder
	 */
	protected function newEntityInfoBuilder( array $ids ) {
		$idParser = new BasicEntityIdParser();

		$repo = new MockRepository();

		foreach ( $this->getKnownEntities() as $entity ) {
			$repo->putEntity( $entity );
		}

		foreach ( $this->getKnownRedirects() as $from => $toId ) {
			$fromId = $idParser->parse( $from );
			$repo->putRedirect( new EntityRedirect( $fromId, $toId ) );
		}

		return new GenericEntityInfoBuilder( $ids, $idParser, $repo );
	}

}
