<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;
use Wikibase\Test\EntityInfoBuilderTest;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Lib\Store\GenericEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
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

		$mockRepository = new MockRepository();

		foreach ( $this->getKnownEntities() as $entity ) {
			$mockRepository->putEntity( $entity );
		}

		foreach ( $this->getKnownRedirects() as $from => $toId ) {
			$fromId = $idParser->parse( $from );
			$mockRepository->putRedirect( new EntityRedirect( $fromId, $toId ) );
		}

		return new GenericEntityInfoBuilder( $ids, $idParser, $mockRepository );
	}

}
