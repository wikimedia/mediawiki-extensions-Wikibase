<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Hooks;

use ChangesList;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Tests\Hooks\LabelPrefetchHookHandlerTestBase;

/**
 * @covers \Wikibase\Repo\Hooks\LabelPrefetchHookHandler
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class LabelPrefetchHookHandlerTest extends LabelPrefetchHookHandlerTestBase {

	public function testDoChangesListInitRows() {
		$rows = [
			(object)[
				'rc_namespace' => NS_MAIN,
				'rc_title' => 'Q1',
				'rc_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:P1]]: asdf",
			],
			(object)[
				'rc_namespace' => NS_MAIN,
				'rc_title' => 'Q2',
				'rc_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:P2]]: asdf",
			],
			(object)[
				'rc_namespace' => NS_MAIN,
				'rc_title' => 'Q3',
				'rc_comment_text' => "/* wbsetclaim-update:1||1 */ [[Property:P3]]: asdf",
			],
		];

		$expectedTermTypes = [ 'label', 'description' ];
		$expectedLanguageCodes = [ 'de', 'en', 'it' ];

		$itemOneId = new ItemId( 'Q1' );
		$itemOne = new Item( $itemOneId );

		$itemOne->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 1 ) ) );
		$itemOne->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 2 ) ) );

		$itemTwoId = new ItemId( 'Q2' );
		$itemTwo = new Item( $itemTwoId );

		$itemTwo->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 1 ) ) );
		$itemTwo->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 3 ) ) );

		$itemThreeNotFoundId = new ItemId( 'Q3' );
		$itemThreeNotFound = null;

		$expectedItemIds = [
			$itemOneId,
			$itemTwoId,
			$itemThreeNotFoundId,
		];

		$expectedPropertyIds = [
			new NumericPropertyId( 'P1' ),
			new NumericPropertyId( 'P2' ),
			new NumericPropertyId( 'P3' ),
		];

		$prefetchingTermLookup = $this->createMock( PrefetchingTermLookup::class );
		$prefetchingTermLookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->willReturnCallback( $this->getPrefetchTermsCallback(
				$expectedPropertyIds,
				$expectedTermTypes,
				$expectedLanguageCodes
			) );

		$linkBeginHookHandler = $this->getLabelPrefetchHookHandlers(
			$this->getPrefetchTermsCallback( $expectedItemIds, $expectedTermTypes, $expectedLanguageCodes ),
			$expectedTermTypes,
			$expectedLanguageCodes,
			$prefetchingTermLookup,
			true
		);

		/** @var ChangesList $changesList */
		$changesList = $this->createMock( ChangesList::class );

		$linkBeginHookHandler->onChangesListInitRows(
			$changesList,
			$rows
		);
	}
}
