<?php

namespace Wikibase\Repo\Tests\Hooks;

use ChangesList;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers \Wikibase\Repo\Hooks\LabelPrefetchHookHandlers
 *
 * @group Wikibase
 * @group Database
 *        ^--- who knows what ChangesList may do internally...
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class LabelPrefetchHookHandlersTest extends LabelPrefetchHookHandlerTestBase {

	public function testDoChangesListInitRows() {
		$rows = [
			(object)[ 'rc_namespace' => NS_MAIN, 'rc_title' => 'XYZ' ],
			(object)[ 'rc_namespace' => NS_MAIN, 'rc_title' => 'Q23' ],
			(object)[ 'rc_namespace' => NS_MAIN, 'rc_title' => 'P55' ],
		];

		$expectedTermTypes = [ 'label', 'description' ];
		$expectedLanguageCodes = [ 'de', 'en', 'it' ];

		$expectedIds = [
			new ItemId( 'Q23' ),
			new PropertyId( 'P55' ),
		];

		$prefetchTerms = $this->getPrefetchTermsCallback( $expectedIds, $expectedTermTypes, $expectedLanguageCodes );

		$linkBeginHookHandler = $this->getLabelPrefetchHookHandlers(
			$prefetchTerms,
			$expectedTermTypes,
			$expectedLanguageCodes
		);

		/** @var ChangesList $changesList */
		$changesList = $this->getMockBuilder( ChangesList::class )
			->disableOriginalConstructor()
			->getMock();

		$linkBeginHookHandler->doChangesListInitRows(
			$changesList,
			$rows
		);
	}

}
