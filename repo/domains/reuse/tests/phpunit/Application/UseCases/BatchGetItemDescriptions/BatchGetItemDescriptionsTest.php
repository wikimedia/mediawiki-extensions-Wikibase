<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptions;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptionsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Description;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Descriptions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemDescriptionsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemRedirectResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptions
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BatchGetItemDescriptionsTest extends TestCase {

	public function testGetItemDescriptionsBatch(): void {
		$enDeDescriptionItem = new Item(
			new ItemId( 'Q1' ),
			new Fingerprint( descriptions: new TermList( [
				new Term( 'en', 'root vegetable' ),
				new Term( 'de', 'Wurzelgemüse' ),
			] ) ),
		);
		$enDescriptionItem = new Item(
			new ItemId( 'Q2' ),
			new Fingerprint( descriptions: new TermList( [ new Term( 'en', 'fruit of the apple tree' ) ] ) ),
		);
		$deDescriptionItem = new Item(
			new ItemId( 'Q3' ),
			new Fingerprint( descriptions: new TermList( [
				new Term( 'de', 'Nahrungsmittel, das aus Mehl, Wasser und weiteren Zutaten gebacken wird' ),
			] ) ),
		);

		$lookup = new InMemoryPrefetchingTermLookup();
		$lookup->setData( [ $enDeDescriptionItem, $enDescriptionItem, $deDescriptionItem ] );

		$q1Descriptions = new Descriptions(
			new Description( 'en', $this->getDescription( $enDeDescriptionItem, 'en' ) ),
			new Description( 'de', $this->getDescription( $enDeDescriptionItem, 'de' ) )
		);
		$expectedDescriptionsBatch = new ItemDescriptionsBatch( [
			'Q1' => $q1Descriptions,
			'Q2' => new Descriptions( new Description( 'en', $this->getDescription( $enDescriptionItem, 'en' ) ) ),
			'Q3' => new Descriptions( new Description( 'de', $this->getDescription( $deDescriptionItem, 'de' ) ) ),
			'Q4' => $q1Descriptions, // Q4 is a redirect to Q1
		] );

		$redirectResolver = $this->createStub( ItemRedirectResolver::class );
		$redirectResolver->method( 'resolveRedirect' )->willReturnCallback(
			fn( ItemId $id ) => $id->getSerialization() === 'Q4' ? new ItemId( 'Q1' ) : $id
		);

		$this->assertEquals(
			$expectedDescriptionsBatch,
			$this->newUseCase( $lookup, $redirectResolver )->execute(
				new BatchGetItemDescriptionsRequest( [ 'Q1', 'Q2', 'Q3', 'Q4' ], [ 'en', 'de' ] )
			)->batch
		);
	}

	private function getDescription( Item $item, string $languageCode ): string {
		return $item->getDescriptions()->getByLanguage( $languageCode )->getText();
	}

	private function newUseCase(
		PrefetchingTermLookup $lookup,
		ItemRedirectResolver $redirectResolver
	): BatchGetItemDescriptions {
		return new BatchGetItemDescriptions(
			new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( $lookup ),
			$redirectResolver
		);
	}

}
