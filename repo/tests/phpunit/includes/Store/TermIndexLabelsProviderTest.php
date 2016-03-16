<?php

namespace Wikibase\Repo\Tests\Store;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Store\TermIndexLabelsProvider;
use Wikibase\TermIndexEntry;
use Wikibase\Test\MockTermIndex;

/**
 *
 * @covers Wikibase\Repo\Store\TermIndexLabelsProvider
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class TermIndexLabelsProviderTest extends PHPUnit_Framework_TestCase {

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

	public function instanceProvider() {
		return [
			[ new ItemId( 'Q111' ), new TermList( [ new Term( 'en', 'Foo' ) ] ) ],
			[ new ItemId( 'Q333' ), new TermList( [ new Term( 'en', 'Food is great' ) ] ) ],
			[ new ItemId( 'Q555' ), new TermList( [] ) ],
			[ new PropertyId( 'P22' ), new TermList( [ new Term( 'en-ca', 'Lama' ) ] ) ],
			[ new PropertyId( 'P44' ), new TermList( [ new Term( 'en', 'Lama' ) ] ) ],
			[ new ItemId( 'Q999999' ), new TermList( [] ) ],
		];
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testLabelsProvider( EntityId $entityId, TermList $expected ) {
		$labelsProvider = new TermIndexLabelsProvider( $this->getMockTermIndex(), $entityId );
		$this->assertTrue( $expected->equals( $labelsProvider->getLabels() ) );
	}

}
