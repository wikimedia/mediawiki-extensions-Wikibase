<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCollector;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangedLanguagesCollector
 *
 * @group Wikibase
 * @group ChangeOp
 * @license GPL-2.0-or-later
 */
class ChangedLanguagesCollectorTest extends \PHPUnit\Framework\TestCase {

	public function changeOpResultsProvider() {
		$entityId = new ItemId( 'Q123' );
		return [
			'Entity changed' => [
				new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
				[ 'en' ],
			],
			'Entity did not change' => [
				new LanguageBoundChangeOpResultStub( $entityId, false, 'en' ),
				[],
			],
			'Multiple changes in same language' => [
				new ChangeOpsResult( $entityId, [
					new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
					new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
				] ),
				[ 'en' ],
			],
			'Multiple changes in different language' => [
				new ChangeOpsResult( $entityId, [
					new LanguageBoundChangeOpResultStub( $entityId, true, 'fr' ),
					new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
				] ),
				[ 'fr', 'en' ],
			],
			'Multiple changes on different tree levels' => [
				new ChangeOpsResult( $entityId, [
					new ChangeOpsResult( $entityId, [
						// should collect this one
						new ChangeOpResultStub( $entityId, true ),
						new ChangeOpResultStub( $entityId, false ),
						new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
						new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
					] ),
					// and collect this one too
					new ChangeOpResultStub( $entityId, true ),
					new ChangeOpResultStub( $entityId, false ),
					new LanguageBoundChangeOpResultStub( $entityId, true, 'fr' ),
					new LanguageBoundChangeOpResultStub( $entityId, false, 'de' ),
				] ),
				[ 'en', 'fr' ],
			],
		];
	}

	/**
	 * @dataProvider changeOpResultsProvider
	 */
	public function testCollectChangedLanguagesAsKey( ChangeOpResult $result, $expectedLanguages ) {
		$changedLanguagesCollector = new ChangedLanguagesCollector();

		$actualLanguages = $changedLanguagesCollector->collectChangedLanguages( $result );

		$this->assertEquals( $expectedLanguages, $actualLanguages );
	}
}
