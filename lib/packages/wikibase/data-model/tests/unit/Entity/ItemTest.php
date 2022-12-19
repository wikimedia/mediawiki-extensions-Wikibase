<?php

namespace Wikibase\DataModel\Tests\Entity;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers \Wikibase\DataModel\Entity\Item
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Michał Łazowik
 */
class ItemTest extends \PHPUnit\Framework\TestCase {

	private function getNewEmpty() {
		return new Item();
	}

	public function testGetId() {
		$item = new Item();
		$this->assertNull( $item->getId() );

		$item->setId( new ItemId( 'Q1' ) );
		$this->assertEquals( new ItemId( 'Q1' ), $item->getId() );

		$item->setId( null );
		$this->assertNull( $item->getId() );

		$item = new Item( new ItemId( 'Q2' ) );
		$this->assertEquals( new ItemId( 'Q2' ), $item->getId() );
	}

	public function testGetSiteLinkWithNonSetSiteId() {
		$item = new Item();

		$this->expectException( OutOfBoundsException::class );
		$item->getSiteLinkList()->getBySiteId( 'enwiki' );
	}

	/**
	 * @dataProvider simpleSiteLinkProvider
	 */
	public function testAddSiteLink( SiteLink $siteLink ) {
		$item = new Item();

		$item->getSiteLinkList()->addSiteLink( $siteLink );

		$this->assertEquals(
			$siteLink,
			$item->getSiteLinkList()->getBySiteId( $siteLink->getSiteId() )
		);
	}

	public function simpleSiteLinkProvider() {
		$argLists = [];

		$argLists[] = [
			new SiteLink(
				'enwiki',
				'Wikidata',
				[
					new ItemId( 'Q42' ),
				]
			),
		];
		$argLists[] = [
			new SiteLink(
				'nlwiki',
				'Wikidata'
			),
		];
		$argLists[] = [
			new SiteLink(
				'enwiki',
				'Nyan!',
				[
					new ItemId( 'Q42' ),
					new ItemId( 'Q149' ),
				]
			),
		];
		$argLists[] = [
			new SiteLink(
				'foo bar',
				'baz bah',
				[
					new ItemId( 'Q3' ),
					new ItemId( 'Q7' ),
				]
			),
		];

		return $argLists;
	}

	public function simpleSiteLinksProvider() {
		$argLists = [];

		$argLists[] = [];

		$argLists[] = [ new SiteLink( 'enwiki', 'Wikidata', [ new ItemId( 'Q42' ) ] ) ];

		$argLists[] = [
			new SiteLink( 'enwiki', 'Wikidata' ),
			new SiteLink( 'nlwiki', 'Wikidata', [ new ItemId( 'Q3' ) ] ),
		];

		$argLists[] = [
			new SiteLink( 'enwiki', 'Wikidata' ),
			new SiteLink( 'nlwiki', 'Wikidata' ),
			new SiteLink( 'foo bar', 'baz bah', [ new ItemId( 'Q2' ) ] ),
		];

		return $argLists;
	}

	public function testHasLinkToSiteForFalse() {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'ENWIKI', 'Wikidata', [ new ItemId( 'Q42' ) ] );

		$this->assertFalse( $item->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ) );
		$this->assertFalse( $item->getSiteLinkList()->hasLinkWithSiteId( 'dewiki' ) );
		$this->assertFalse( $item->getSiteLinkList()->hasLinkWithSiteId( 'foo bar' ) );
	}

	public function testHasLinkToSiteForTrue() {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Wikidata', [ new ItemId( 'Q42' ) ] );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Wikidata' );
		$item->getSiteLinkList()->addNewSiteLink( 'foo bar', 'Wikidata' );

		$this->assertTrue( $item->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ) );
		$this->assertTrue( $item->getSiteLinkList()->hasLinkWithSiteId( 'dewiki' ) );
		$this->assertTrue( $item->getSiteLinkList()->hasLinkWithSiteId( 'foo bar' ) );
	}

	public function testEmptyItemReturnsEmptySiteLinkList() {
		$item = new Item();
		$this->assertTrue( $item->getSiteLinkList()->isEmpty() );
	}

	public function testAddSiteLinkOverridesOldLinks() {
		$item = new Item();

		$item->getSiteLinkList()->addNewSiteLink( 'kittens', 'foo' );

		$newLink = new SiteLink( 'kittens', 'bar' );
		$item->addSiteLink( $newLink );

		$this->assertTrue( $item->getSiteLinkList()->getBySiteId( 'kittens' )->equals( $newLink ) );
	}

	public function testEmptyItemIsEmpty() {
		$item = new Item();
		$this->assertTrue( $item->isEmpty() );
	}

	public function testItemWithIdIsEmpty() {
		$item = new Item( new ItemId( 'Q1337' ) );
		$this->assertTrue( $item->isEmpty() );
	}

	public function testItemWithStuffIsNotEmpty() {
		$item = new Item();
		$item->setAliases( 'en', [ 'foo' ] );
		$this->assertFalse( $item->isEmpty() );

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'en', 'o_O' );
		$this->assertFalse( $item->isEmpty() );

		$item = new Item();
		$item->getStatements()->addStatement( $this->newStatement() );
		$this->assertFalse( $item->isEmpty() );
	}

	public function testItemWithSitelinksHasSitelinks() {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'en', 'foo' );
		$this->assertFalse( $item->getSiteLinkList()->isEmpty() );
	}

	public function testItemWithoutSitelinksHasNoSitelinks() {
		$item = new Item();
		$this->assertTrue( $item->getSiteLinkList()->isEmpty() );
	}

	private function newStatement() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'kittens' );
		return $statement;
	}

	public function testEmptyConstructor() {
		$item = new Item();

		$this->assertNull( $item->getId() );
		$this->assertTrue( $item->getFingerprint()->isEmpty() );
		$this->assertTrue( $item->getLabels()->isEmpty() );
		$this->assertTrue( $item->getDescriptions()->isEmpty() );
		$this->assertTrue( $item->getAliasGroups()->isEmpty() );
		$this->assertTrue( $item->getSiteLinkList()->isEmpty() );
		$this->assertTrue( $item->getStatements()->isEmpty() );
	}

	public function testCanConstructWithStatementList() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'meh' );

		$statements = new StatementList( $statement );

		$item = new Item( null, null, null, $statements );

		$this->assertEquals(
			$statements,
			$item->getStatements()
		);
	}

	public function testSetStatements() {
		$item = new Item();
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$item->setStatements( new StatementList() );
		$this->assertTrue( $item->getStatements()->isEmpty() );
	}

	public function equalsProvider() {
		$firstItem = new Item();
		$firstItem->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$secondItem = new Item();
		$secondItem->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$secondItemWithId = $secondItem->copy();
		$secondItemWithId->setId( new ItemId( 'Q42' ) );

		$differentId = $secondItemWithId->copy();
		$differentId->setId( new ItemId( 'Q43' ) );

		return [
			[ new Item(), new Item() ],
			[ $firstItem, $secondItem ],
			[ $secondItem, $secondItemWithId ],
			[ $secondItemWithId, $differentId ],
		];
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( Item $firstItem, Item $secondItem ) {
		$this->assertTrue( $firstItem->equals( $secondItem ) );
		$this->assertTrue( $secondItem->equals( $firstItem ) );
	}

	/**
	 * @return Item
	 */
	private function getBaseItem() {
		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', 'Same' );
		$item->setDescription( 'en', 'Same' );
		$item->setAliases( 'en', [ 'Same' ] );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Same' );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		return $item;
	}

	public function notEqualsProvider() {
		$differentLabel = $this->getBaseItem();
		$differentLabel->setLabel( 'en', 'Different' );

		$differentDescription = $this->getBaseItem();
		$differentDescription->setDescription( 'en', 'Different' );

		$differentAlias = $this->getBaseItem();
		$differentAlias->setAliases( 'en', [ 'Different' ] );

		$differentSiteLink = $this->getBaseItem();
		$differentSiteLink->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );
		$differentSiteLink->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Different' );

		$differentStatement = $this->getBaseItem();
		$differentStatement->setStatements( new StatementList() );
		$differentStatement->getStatements()->addNewStatement( new PropertyNoValueSnak( 24 ) );

		$item = $this->getBaseItem();

		return [
			'empty' => [ $item, new Item() ],
			'label' => [ $item, $differentLabel ],
			'description' => [ $item, $differentDescription ],
			'alias' => [ $item, $differentAlias ],
			'siteLink' => [ $item, $differentSiteLink ],
			'statement' => [ $item, $differentStatement ],
		];
	}

	/**
	 * @dataProvider notEqualsProvider
	 */
	public function testNotEquals( Item $firstItem, Item $secondItem ) {
		$this->assertFalse( $firstItem->equals( $secondItem ) );
		$this->assertFalse( $secondItem->equals( $firstItem ) );
	}

	public function cloneProvider() {
		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'en', 'original' );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Original' );

		return [
			'copy' => [ $item, $item->copy() ],
			'native clone' => [ $item, clone $item ],
		];
	}

	/**
	 * @dataProvider cloneProvider
	 */
	public function testCloneIsEqualButNotIdentical( Item $original, Item $clone ) {
		$this->assertNotSame( $original, $clone );
		$this->assertTrue( $original->equals( $clone ) );
		$this->assertSame(
			$original->getId(),
			$clone->getId(),
			'id is immutable and must not be cloned'
		);

		// The clone must not reference the same mutable objects
		$this->assertNotSame( $original->getFingerprint(), $clone->getFingerprint() );
		$this->assertNotSame( $original->getStatements(), $clone->getStatements() );
		$this->assertNotSame(
			$original->getStatements()->getFirstStatementWithGuid( null ),
			$clone->getStatements()->getFirstStatementWithGuid( null )
		);
		$this->assertNotSame( $original->getSiteLinkList(), $clone->getSiteLinkList() );
		$this->assertSame(
			$original->getSiteLinkList()->getBySiteId( 'enwiki' ),
			$clone->getSiteLinkList()->getBySiteId( 'enwiki' ),
			'SiteLink is immutable and must not be cloned'
		);
	}

	/**
	 * @dataProvider cloneProvider
	 */
	public function testOriginalDoesNotChangeWithClone( Item $original, Item $clone ) {
		$originalStatement = $original->getStatements()->getFirstStatementWithGuid( null );
		$clonedStatement = $clone->getStatements()->getFirstStatementWithGuid( null );

		$clone->setLabel( 'en', 'clone' );
		$clone->setDescription( 'en', 'clone' );
		$clone->setAliases( 'en', [ 'clone' ] );
		$clonedStatement->setGuid( 'clone' );
		$clonedStatement->setMainSnak( new PropertySomeValueSnak( 666 ) );
		$clonedStatement->setRank( Statement::RANK_DEPRECATED );
		$clonedStatement->getQualifiers()->addSnak( new PropertyNoValueSnak( 1 ) );
		$clonedStatement->getReferences()->addNewReference( new PropertyNoValueSnak( 1 ) );
		$clone->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );

		$this->assertSame( 'original', $original->getFingerprint()->getLabel( 'en' )->getText() );
		$this->assertFalse( $original->getFingerprint()->hasDescription( 'en' ) );
		$this->assertFalse( $original->getFingerprint()->hasAliasGroup( 'en' ) );
		$this->assertNull( $originalStatement->getGuid() );
		$this->assertSame( 'novalue', $originalStatement->getMainSnak()->getType() );
		$this->assertSame( Statement::RANK_NORMAL, $originalStatement->getRank() );
		$this->assertTrue( $originalStatement->getQualifiers()->isEmpty() );
		$this->assertTrue( $originalStatement->getReferences()->isEmpty() );
		$this->assertFalse( $original->getSiteLinkList()->isEmpty() );
	}

	// Below are tests copied from EntityTest

	public function labelProvider() {
		return [
			[ 'en', 'spam' ],
			[ 'en', 'spam', 'spam' ],
			[ 'de', 'foo bar baz' ],
		];
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testSetLabel( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$entity->setLabel( $languageCode, $labelText );

		$this->assertSame( $labelText, $entity->getFingerprint()->getLabel( $languageCode )->getText() );

		$entity->setLabel( $languageCode, $moarText );

		$this->assertSame( $moarText, $entity->getFingerprint()->getLabel( $languageCode )->getText() );
	}

	public function descriptionProvider() {
		return [
			[ 'en', 'spam' ],
			[ 'en', 'spam', 'spam' ],
			[ 'de', 'foo bar baz' ],
		];
	}

	/**
	 * @dataProvider descriptionProvider
	 * @param string $languageCode
	 * @param string $description
	 * @param string $moarText
	 */
	public function testSetDescription( $languageCode, $description, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$entity->setDescription( $languageCode, $description );

		$this->assertSame( $description, $entity->getFingerprint()->getDescription( $languageCode )->getText() );

		$entity->setDescription( $languageCode, $moarText );

		$this->assertSame( $moarText, $entity->getFingerprint()->getDescription( $languageCode )->getText() );
	}

	public function aliasesProvider() {
		return [
			[ [
				'en' => [ [ 'spam' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar', 'baz' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar' ], [ 'baz', 'spam' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar', 'baz' ] ],
				'de' => [ [ 'foobar' ], [ 'baz' ] ],
			] ],
			// with duplicates
			[ [
				'en' => [ [ 'spam', 'ham', 'ham' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar' ], [ 'bar', 'spam' ] ],
			] ],
		];
	}

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testSetAliases( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			foreach ( $aliasesList as $aliases ) {
				$entity->setAliases( $langCode, $aliases );
			}
		}

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$expected = array_values( array_unique( array_pop( $aliasesList ) ) );
			$actual = $entity->getFingerprint()->getAliasGroup( $langCode )->getAliases();
			$this->assertSame( $expected, $actual );
		}
	}

	public function testSetEmptyAlias() {
		$item = new Item();

		$item->setAliases( 'en', [ 'wind', 'air', '', 'fire' ] );
		$this->assertSame(
			[ 'wind', 'air', 'fire' ],
			$item->getAliasGroups()->getByLanguage( 'en' )->getAliases()
		);

		$item->setAliases( 'en', [ '', '' ] );
		$this->assertFalse( $item->getAliasGroups()->hasGroupForLanguage( 'en' ) );
	}

	public function instanceProvider() {
		$entities = [];

		// empty
		$entity = $this->getNewEmpty();
		$entities[] = $entity;

		// ID only
		$entity = clone $entity;
		$entity->setId( new ItemId( 'Q44' ) );

		$entities[] = $entity;

		// with labels and stuff
		$entity = $this->getNewEmpty();
		$entity->setAliases( 'en', [ 'o', 'noez' ] );
		$entity->setLabel( 'de', 'spam' );
		$entity->setDescription( 'en', 'foo bar baz' );

		$entities[] = $entity;

		// with labels etc and ID
		$entity = clone $entity;
		$entity->setId( new ItemId( 'Q42' ) );

		$entities[] = $entity;

		$argLists = [];

		foreach ( $entities as $entity ) {
			$argLists[] = [ $entity ];
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Item $entity
	 */
	public function testCopy( Item $entity ) {
		$copy = $entity->copy();

		// The equality method alone is not enough since it does not check the IDs.
		$this->assertTrue( $entity->equals( $copy ) );
		$this->assertEquals( $entity->getId(), $copy->getId() );

		$this->assertNotSame( $entity, $copy );
	}

	public function testCopyRetainsLabels() {
		$item = new Item();

		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$item->getFingerprint()->setLabel( 'de', 'bar' );

		$newItem = $item->copy();

		$this->assertTrue( $newItem->getFingerprint()->getLabels()->hasTermForLanguage( 'en' ) );
		$this->assertTrue( $newItem->getFingerprint()->getLabels()->hasTermForLanguage( 'de' ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Item $entity
	 */
	public function testSerialize( Item $entity ) {
		$string = serialize( $entity );

		$this->assertIsString( $string );

		$instance = unserialize( $string );

		$this->assertTrue( $entity->equals( $instance ) );
		$this->assertEquals( $entity->getId(), $instance->getId() );
	}

	public function testWhenNoStuffIsSet_getFingerprintReturnsEmptyFingerprint() {
		$entity = $this->getNewEmpty();

		$this->assertEquals(
			new Fingerprint(),
			$entity->getFingerprint()
		);
	}

	public function testWhenLabelsAreSet_getFingerprintReturnsFingerprintWithLabels() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setLabel( 'de', 'bar' );

		$this->assertEquals(
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
				] )
			),
			$entity->getFingerprint()
		);
	}

	public function testWhenTermsAreSet_getFingerprintReturnsFingerprintWithTerms() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setDescription( 'en', 'foo bar' );
		$entity->setAliases( 'en', [ 'foo', 'bar' ] );

		$this->assertEquals(
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo bar' ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] )
			),
			$entity->getFingerprint()
		);
	}

	public function testGivenEmptyFingerprint_noTermsAreSet() {
		$entity = $this->getNewEmpty();
		$entity->setFingerprint( new Fingerprint() );

		$this->assertTrue( $entity->getFingerprint()->isEmpty() );
	}

	public function testGivenEmptyFingerprint_existingTermsAreRemoved() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setDescription( 'en', 'foo bar' );
		$entity->setAliases( 'en', [ 'foo', 'bar' ] );

		$entity->setFingerprint( new Fingerprint() );

		$this->assertTrue( $entity->getFingerprint()->isEmpty() );
	}

	public function testWhenSettingFingerprint_getFingerprintReturnsIt() {
		$fingerprint = new Fingerprint(
			new TermList( [
				new Term( 'en', 'english label' ),
			] ),
			new TermList( [
				new Term( 'en', 'english description' ),
			] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'first en alias', 'second en alias' ] ),
			] )
		);

		$entity = $this->getNewEmpty();
		$entity->setFingerprint( $fingerprint );
		$newFingerprint = $entity->getFingerprint();

		$this->assertSame( $fingerprint, $newFingerprint );
	}

	public function testGetLabels() {
		$item = new Item();
		$item->setLabel( 'en', 'foo' );

		$this->assertEquals(
			new TermList( [
				new Term( 'en', 'foo' ),
			] ),
			$item->getLabels()
		);
	}

	public function testGetDescriptions() {
		$item = new Item();
		$item->setDescription( 'en', 'foo bar' );

		$this->assertEquals(
			new TermList( [
				new Term( 'en', 'foo bar' ),
			] ),
			$item->getDescriptions()
		);
	}

	public function testGetAliasGroups() {
		$item = new Item();
		$item->setAliases( 'en', [ 'foo', 'bar' ] );

		$this->assertEquals(
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'foo', 'bar' ] ),
			] ),
			$item->getAliasGroups()
		);
	}

	public function testGetLabels_sameListAsFingerprint() {
		$item = new Item();

		$this->assertSame(
			$item->getFingerprint()->getLabels(),
			$item->getLabels()
		);
	}

	public function testGetDescriptions_sameListAsFingerprint() {
		$item = new Item();

		$this->assertSame(
			$item->getFingerprint()->getDescriptions(),
			$item->getDescriptions()
		);
	}

	public function testGetAliasGroups_sameListAsFingerprint() {
		$item = new Item();

		$this->assertSame(
			$item->getFingerprint()->getAliasGroups(),
			$item->getAliasGroups()
		);
	}

	/**
	 * @dataProvider clearableProvider
	 */
	public function testClear( Item $item ) {
		$clone = $item->copy();

		$item->clear();

		$this->assertEquals( $clone->getId(), $item->getId(), 'cleared Item should keep its id' );
		$this->assertTrue( $item->isEmpty(), 'cleared Item should be empty' );
	}

	public function clearableProvider() {
		return [
			'empty' => [ new Item( new ItemId( 'Q23' ) ) ],
			'with fingerprint' => [
				new Item(
					new ItemId( 'Q42' ),
					new Fingerprint( new TermList( [ new Term( 'en', 'foo' ) ] ) )
				),
			],
			'with sitelink' => [
				new Item(
					new ItemId( 'Q123' ),
					null,
					new SiteLinkList( [ new SiteLink( 'enwiki', 'Wikidata' ) ] )
				),
			],
			'with statement' => [
				new Item(
					new ItemId( 'Q321' ),
					null,
					null,
					new StatementList( $this->newStatement() )
				),
			],
		];
	}

}
