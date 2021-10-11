<?php
declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Rdf;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfBuilder;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\RdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class RdfBuilderFactoryTest extends MediaWikiIntegrationTestCase {

	public function testGetRdfBuilder() {
		$factory = $this->getFactory();
		$this->assertInstanceOf(
			RdfBuilder::class,
			$factory->getRdfBuilder(
				0,
				$this->createMock( DedupeBag::class ),
				$this->createMock( RdfWriter::class )
			)
		);
	}

	private function getFactory(): RdfBuilderFactory {
		return new RdfBuilderFactory(
			$this->createMock( RdfVocabulary::class ),
			$this->createMock( EntityRdfBuilderFactory::class ),
			$this->createMock( EntityContentFactory::class ),
			$this->createMock( EntityStubRdfBuilderFactory::class ),
			$this->createMock( EntityRevisionLookup::class )
		);
	}

}
