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
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemRedirectResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BatchGetItemLabelsTest extends TestCase {

	private InMemoryPrefetchingTermLookup $lookup;

	public function setUp(): void {
		$enArLabelItem = new Item(
			new ItemId( 'Q1' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Potato' ), new Term( 'ar', 'بطاطا' ) ] ) ),
		);
		$enLabelItem = new Item(
			new ItemId( 'Q2' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Apple' ) ] ) ),
		);
		$arLabelItem = new Item(
			new ItemId( 'Q3' ),
			new Fingerprint( new TermList( [ new Term( 'ar', 'خبز' ) ] ) ),
		);

		$this->lookup = new InMemoryPrefetchingTermLookup();
		$this->lookup->setData( [ $enArLabelItem, $enLabelItem, $arLabelItem ] );
	}

	public function testGetItemLabelsBatch(): void {
		$q1Labels = new Labels( new Label( 'en', 'Potato' ), new Label( 'ar', 'بطاطا' ) );
		$expectedLabelsBatch = new ItemLabelsBatch( [
			'Q1' => $q1Labels,
			'Q2' => new Labels( new Label( 'en', 'Apple' ) ),
			'Q3' => new Labels( new Label( 'ar', 'خبز' ) ),
			'Q4' => $q1Labels, // Q4 is a redirect to Q1
		] );

		$redirectResolver = $this->createStub( ItemRedirectResolver::class );
		$redirectResolver->method( 'resolveRedirect' )->willReturnCallback(
			fn( ItemId $id ) => $id->getSerialization() === 'Q4' ? new ItemId( 'Q1' ) : $id
		);

		$this->assertEquals(
			$expectedLabelsBatch,
			$this->newUseCase( $redirectResolver )->execute(
				new BatchGetItemLabelsRequest( [ 'Q1', 'Q2', 'Q3', 'Q4' ], [ 'ar', 'en' ] )
			)->batch
		);
	}

	private function newUseCase( ItemRedirectResolver $redirectResolver ): BatchGetItemLabels {
		return new BatchGetItemLabels(
			new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( $this->lookup ),
			$redirectResolver,
		);
	}

}
