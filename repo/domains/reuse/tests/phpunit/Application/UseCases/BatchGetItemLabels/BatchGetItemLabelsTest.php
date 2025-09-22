<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Application\UseCases\BatchGetItemLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabelsRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabelsResponse;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchItemLabelsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchItemLabelsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BatchGetItemLabelsTest extends TestCase {

	private InMemoryPrefetchingTermLookup $lookup;

	public function setUp(): void {
		$q1 = new Item(
			new ItemId( 'Q1' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Potato' ), new Term( 'ar', 'بطاطا' ) ] ) ),
		);
		$q2 = new Item(
			new ItemId( 'Q2' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Apple' ), new Term( 'ar', 'تفاح' ) ] ) ),
		);
		$q3 = new Item(
			new ItemId( 'Q3' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Bread' ), new Term( 'ar', 'خبز' ) ] ) ),
		);

		$this->lookup = new InMemoryPrefetchingTermLookup();
		$this->lookup->setData( [ $q1, $q2, $q3 ] );
	}

	public function testGetItemLabelsBatch(): void {
		$expectedLabelsBatch = new BatchGetItemLabelsResponse(
			new ItemLabelsBatch( [
				'Q1' => new Labels( new Label( 'en', 'Potato' ), new Label( 'ar', 'بطاطا' ) ),
				'Q3' => new Labels( new Label( 'en', 'Bread' ), new Label( 'ar', 'خبز' ) ),
			] )
		);

		$this->assertEquals(
			$expectedLabelsBatch,
			$this->newUseCase()->execute(
				new BatchGetItemLabelsRequest( [ new ItemId( 'Q3' ), new ItemId( 'Q1' ) ], [ 'ar', 'en' ] )
			)
		);
	}

	private function newUseCase(): BatchGetItemLabels {
		return new BatchGetItemLabels(
			new PrefetchingTermLookupBatchItemLabelsRetriever( $this->lookup )
		);
	}

}
