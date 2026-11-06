<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Rdf;

use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\Rdf\ItemStubRdfBuilder;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\ItemStubRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class ItemStubRdfBuilderTest extends MediaWikiIntegrationTestCase {

	private const array LANGUAGES = [ 'pl', 'en' ];

	private PrefetchingTermLookup $termLookup;

	protected function setUp(): void {
		$this->termLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->termLookup->method( 'getLabels' )->willReturn( [] );
		$this->termLookup->method( 'getDescriptions' )->willReturn( [] );
	}

	private function getBuilder(): ItemStubRdfBuilder {
		$vocab = $this->createMock( RdfVocabulary::class );
		$vocab->entityNamespaceNames[''] = 'cat';
		$vocab->method( 'getEntityRepositoryName' )->willReturn( '' );

		return new ItemStubRdfBuilder(
			$this->termLookup,
			$vocab,
			$this->createNoOpMock( RdfWriter::class ),
			[],
			self::LANGUAGES
		);
	}

	public function testAddEntityStub() {
		$itemId = new ItemId( 'Q15436' );
		$this->termLookup->expects( $this->atLeastOnce() )->method( 'getLabels' )->with( $itemId, self::LANGUAGES );
		$this->termLookup->expects( $this->atLeastOnce() )->method( 'getDescriptions' )->with( $itemId, self::LANGUAGES );

		$builder = $this->getBuilder();
		$builder->addEntityStub( $itemId );
	}

	public function testPrefetchingHappens() {
		$itemId = new ItemId( 'Q15436' );
		$this->termLookup->expects( $this->atLeastOnce() )
			->method( 'getLabels' )
			->with( $itemId, self::LANGUAGES );
		$this->termLookup->expects( $this->atLeastOnce() )
			->method( 'getDescriptions' )
			->with( $itemId, self::LANGUAGES );
		$this->termLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchTerms' )
			->with(
				[ $itemId ],
				[ TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_LABEL ],
				self::LANGUAGES
			);

		$builder = $this->getBuilder();
		$builder->markForPrefetchingEntityStub( $itemId );
		$builder->addEntityStub( $itemId );
	}

}
