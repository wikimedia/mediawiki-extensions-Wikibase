<?php

namespace Wikibase\Repo\Tests\Store;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\Store\TermIndexAliasesProvider;
use Wikibase\TermIndexEntry;
use Wikibase\Test\MockTermIndex;

/**
 *
 * @covers Wikibase\Repo\Store\TermIndexAliasesProvider
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class TermIndexAliasesProviderTest extends PHPUnit_Framework_TestCase {

	private function getMockTermIndex() {
		return new MockTermIndex(
			array(
				//Q111 - Has label, description and alias all the same
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_DESCRIPTION, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'FOO', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q111' ) ),
				//Q333
				$this->getTermIndexEntry( 'Food is great', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q333' ) ),
				//Q555
				$this->getTermIndexEntry( 'Ta', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				$this->getTermIndexEntry( 'Taa', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				$this->getTermIndexEntry( 'TAAA', 'en-ca', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				$this->getTermIndexEntry( 'Taa', 'en-ca', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				//P22
				$this->getTermIndexEntry( 'Lama', 'en-ca', TermIndexEntry::TYPE_LABEL, new PropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'La-description', 'en', TermIndexEntry::TYPE_DESCRIPTION, new PropertyId( 'P22' ) ),
				//P44
				$this->getTermIndexEntry( 'Lama', 'en', TermIndexEntry::TYPE_LABEL, new PropertyId( 'P44' ) ),
				$this->getTermIndexEntry( 'Lama-de-desc', 'de', TermIndexEntry::TYPE_DESCRIPTION, new PropertyId( 'P44' ) ),
			)
		);
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $termType
	 * @param EntityId $entityId
	 *
	 * @return TermIndexEntry
	 */
	private function getTermIndexEntry( $text, $languageCode, $termType, EntityId $entityId ) {
		return new TermIndexEntry( [
			'termText' => $text,
			'termLanguage' => $languageCode,
			'termType' => $termType,
			'entityId' => $entityId->getNumericId(),
			'entityType' => $entityId->getEntityType(),
		] );
	}

	public function dataProvider() {
		return [
			[ new ItemId( 'Q111' ), new AliasGroupList( [ new AliasGroup( 'en', [ 'Foo', 'FOO' ] ) ] ) ],
			[ new ItemId( 'Q333' ), new AliasGroupList( [] ) ],
			[
				new ItemId( 'Q555' ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'Ta', 'Taa' ] ),
					new AliasGroup( 'en-ca', [ 'TAAA', 'Taa' ] )
				] )
			],
			[ new PropertyId( 'P22' ), new AliasGroupList( [] ) ],
			[ new PropertyId( 'P44' ), new AliasGroupList( [] ) ],
			[ new ItemId( 'Q999999' ), new AliasGroupList( [] ) ],
		];
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testAliasesProvider( EntityId $entityId, AliasGroupList $expected ) {
		$labelsProvider = new TermIndexAliasesProvider( $this->getMockTermIndex(), $entityId );
		$this->assertTrue( $expected->equals( $labelsProvider->getAliasGroups() ) );
	}

}
