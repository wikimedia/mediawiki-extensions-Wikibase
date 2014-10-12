<?php

namespace Wikibase\Test\Entity;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @deprecated
 * This test class is to be phased out, and should not be used from outside of the component!
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns several more or less complex claims
	 *
	 * @return Claim[]
	 */
	public abstract function makeClaims();

	/**
	 * @since 0.1
	 *
	 * @return Entity
	 */
	protected abstract function getNewEmpty();

	public function labelProvider() {
		return array(
			array( 'en', 'spam' ),
			array( 'en', 'spam', 'spam' ),
			array( 'de', 'foo bar baz' ),
		);
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

		$this->assertEquals( $labelText, $entity->getLabel( $languageCode ) );

		$entity->setLabel( $languageCode, $moarText );

		$this->assertEquals( $moarText, $entity->getLabel( $languageCode ) );
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 */
	public function testGetLabel( $languageCode, $labelText ) {
		$entity = $this->getNewEmpty();

		$this->assertFalse( $entity->getLabel( $languageCode ) );

		$entity->setLabel( $languageCode, $labelText );

		$this->assertEquals( $labelText, $entity->getLabel( $languageCode ) );
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 */
	public function testRemoveLabel( $languageCode, $labelText ) {
		$entity = $this->getNewEmpty();
		$entity->setLabel( $languageCode, $labelText );
		$entity->removeLabel( $languageCode );
		$this->assertFalse( $entity->getLabel( $languageCode ) );
	}

	public function descriptionProvider() {
		return array(
			array( 'en', 'spam' ),
			array( 'en', 'spam', 'spam' ),
			array( 'de', 'foo bar baz' ),
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testSetDescription( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$entity->setDescription( $languageCode, $labelText );

		$this->assertEquals( $labelText, $entity->getDescription( $languageCode ) );

		$entity->setDescription( $languageCode, $moarText );

		$this->assertEquals( $moarText, $entity->getDescription( $languageCode ) );
	}

	/**
	 * @dataProvider descriptionProvider
	 * @param string $languageCode
	 * @param string $labelText
	 */
	public function testGetDescription( $languageCode, $labelText ) {
		$entity = $this->getNewEmpty();

		$this->assertFalse( $entity->getDescription( $languageCode ) );

		$entity->setDescription( $languageCode, $labelText );

		$this->assertEquals( $labelText, $entity->getDescription( $languageCode ) );
	}

	/**
	 * @dataProvider descriptionProvider
	 * @param string $languageCode
	 * @param string $labelText
	 */
	public function testRemoveDescription( $languageCode, $labelText ) {
		$entity = $this->getNewEmpty();
		$entity->setDescription( $languageCode, $labelText );
		$entity->removeDescription( $languageCode );
		$this->assertFalse( $entity->getDescription( $languageCode ) );
	}

	public function aliasesProvider() {
		return array(
			array( array(
				'en' => array( array( 'spam' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar' ), array( 'baz', 'spam' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ) ),
				'de' => array( array( 'foobar' ), array( 'baz' ) ),
			) ),
			// with duplicates
			array( array(
				'en' => array( array( 'spam', 'ham', 'ham' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar' ), array( 'bar', 'spam' ) )
			) ),
		);
	}

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testAddAliases( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			foreach ( $aliasesList as $aliases ) {
				$entity->addAliases( $langCode, $aliases );
			}
		}

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$expected = array_values( array_unique( call_user_func_array( 'array_merge', $aliasesList ) ) );
			asort( $expected );

			$actual = $entity->getAliases( $langCode );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
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
			asort( $aliasesList );

			$actual = $entity->getAliases( $langCode );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testSetEmptyAlias( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			foreach ( $aliasesList as $aliases ) {
				$entity->setAliases( $langCode, $aliases );
			}
		}
		$entity->setAliases( 'zh', array( 'wind', 'air', '', 'fire') );
		$entity->setAliases( 'zu', array( '', '') );

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$expected = array_values( array_unique( array_pop( $aliasesList ) ) );
			asort( $aliasesList );

			$actual = $entity->getAliases( $langCode );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testSetAllAliases( array $aliasGroups ) {
		$entity = $this->getNewEmpty();
		$entity->addAliases( 'zh' , array( 'qwertyuiop123' , '321poiuytrewq' ) );

		$aliasesToSet = array();
		foreach ( $aliasGroups as $langCode => $aliasGroup ) {
			foreach ( $aliasGroup as $aliases ) {
				$aliasesToSet[$langCode]= $aliases;
			}
		}

		$entity->setAllAliases( $aliasesToSet );

		foreach ( $aliasGroups as $langCode => $aliasGroup ) {
			$expected = array_values( array_unique( array_pop( $aliasGroup ) ) );
			asort( $aliasGroup );

			$actual = $entity->getFingerprint()->getAliasGroups()->getByLanguage( $langCode )->getAliases();
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}

		/** @var AliasGroup $aliasGroup */
		foreach ( $entity->getFingerprint()->getAliasGroups() as $langCode => $aliasGroup ) {
			$this->assertEquals( $aliasGroup->getAliases(), array_unique( $aliasesToSet[$langCode] ) );
		}
	}

	public function testGetAliases() {
		$entity = $this->getNewEmpty();
		$aliases =  array( 'a', 'b' );

		$entity->getFingerprint()->setAliasGroup( 'en', $aliases );

		$this->assertEquals(
			$aliases,
			$entity->getAliases( 'en' )
		);
	}

	public function duplicateAliasesProvider() {
		return array(
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ), array( 'foo', 'bar', 'baz' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ), array( 'foo', 'bar' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar' ), array( 'foo', 'bar', 'baz' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar' ), array( 'bar', 'baz' ) ),
				'de' => array( array(), array( 'foo' ) ),
				'nl' => array( array( 'foo' ), array() ),
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ), array( 'foo', 'bar', 'baz', 'foo', 'bar' ) )
			) ),
		);
	}

	/**
	 * @dataProvider duplicateAliasesProvider
	 */
	public function testRemoveAliases( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$aliases = array_shift( $aliasesList );
			$removedAliases =  array_shift( $aliasesList );

			$entity->setAliases( $langCode, $aliases );
			$entity->removeAliases( $langCode, $removedAliases );

			$expected = array_values( array_diff( $aliases, $removedAliases ) );
			$actual = $entity->getAliases( $langCode );

			asort( $expected );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	public function instanceProvider() {
		$entities = array();

		// empty
		$entity = $this->getNewEmpty();
		$entities[] = $entity;

		// ID only
		$entity = clone $entity;
		$entity->setId( 44 );

		$entities[] = $entity;

		// with labels and stuff
		$entity = $this->getNewEmpty();
		$entity->setAliases( 'en', array( 'o', 'noez' ) );
		$entity->setLabel( 'de', 'spam' );
		$entity->setDescription( 'en', 'foo bar baz' );

		$entities[] = $entity;

		// with labels etc and ID
		$entity = clone $entity;
		$entity->setId( 42 );

		$entities[] = $entity;

		$argLists = array();

		foreach ( $entities as $entity ) {
			$argLists[] = array( $entity );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testCopy( Entity $entity ) {
		$copy = $entity->copy();

		// The equality method alone is not enough since it does not check the IDs.
		$this->assertTrue( $entity->equals( $copy ) );
		$this->assertEquals( $entity->getId(), $copy->getId() );

		$this->assertFalse( $entity === $copy );
	}

	public function testCopyRetainsLabels() {
		$item = Item::newEmpty();

		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$item->getFingerprint()->setLabel( 'de', 'bar' );

		$newItem = unserialize( serialize( $item ) );

		$this->assertTrue( $newItem->getFingerprint()->getLabels()->hasTermForLanguage( 'en' ) );
		$this->assertTrue( $newItem->getFingerprint()->getLabels()->hasTermForLanguage( 'de' ) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testSerialize( Entity $entity ) {
		$string = serialize( $entity );

		$this->assertInternalType( 'string', $string );

		$instance = unserialize( $string );

		$this->assertTrue( $entity->equals( $instance ) );
		$this->assertEquals( $entity->getId(), $instance->getId() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testHasClaims( Entity $entity ) {
		$has = $entity->hasClaims();
		$this->assertInternalType( 'boolean', $has );

		$this->assertEquals( count( $entity->getClaims() ) !== 0, $has );
	}

	/**
	 * Tests Entity::newClaim
	 *
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testNewClaim( Entity $entity ) {
		if ( $entity->getId() === null ) {
			$entity->setId( 50 );
		}

		$snak = new PropertyNoValueSnak( 42 );
		$claim = new Statement( new Claim( $snak ) );
		$claim->setGuid( 'q42$foobarbaz' );

		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claim', $claim );

		$this->assertTrue( $snak->equals( $claim->getMainSnak() ) );

		$guid = $claim->getGuid();

		$this->assertInternalType( 'string', $guid );
	}

	public function diffProvider() {
		$argLists = array();

		$emptyDiff = EntityDiff::newForType( $this->getNewEmpty()->getType() );

		$entity0 = $this->getNewEmpty();
		$entity1 = $this->getNewEmpty();
		$expected = clone $emptyDiff;

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = $this->getNewEmpty();
		$entity0->addAliases( 'nl', array( 'bah' ) );
		$entity0->addAliases( 'de', array( 'bah' ) );

		$entity1 = $this->getNewEmpty();
		$entity1->addAliases( 'en', array( 'foo', 'bar' ) );
		$entity1->addAliases( 'nl', array( 'bah', 'baz' ) );

		$entity1->setDescription( 'en', 'onoez' );

		$expected = new EntityDiff( array(
			'aliases' => new Diff( array(
				'en' => new Diff( array(
					new DiffOpAdd( 'foo' ),
					new DiffOpAdd( 'bar' ),
				), false ),
				'de' => new Diff( array(
					new DiffOpRemove( 'bah' ),
				), false ),
				'nl' => new Diff( array(
					new DiffOpAdd( 'baz' ),
				), false )
			) ),
			'description' => new Diff( array(
				'en' => new DiffOpAdd( 'onoez' ),
			) ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = clone $entity1;
		$entity1 = clone $entity1;
		$expected = clone $emptyDiff;

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = $this->getNewEmpty();

		$entity1 = $this->getNewEmpty();
		$entity1->setLabel( 'en', 'onoez' );

		$expected = new EntityDiff( array(
			'label' => new Diff( array(
				'en' => new DiffOpAdd( 'onoez' ),
			) ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		return $argLists;
	}

	/**
	 * @dataProvider diffProvider
	 *
	 * @param Entity $entity0
	 * @param Entity $entity1
	 * @param EntityDiff $expected
	 */
	public function testDiffEntities( Entity $entity0, Entity $entity1, EntityDiff $expected ) {
		$actual = $entity0->getDiff( $entity1 );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Diff\EntityDiff', $actual );
		$this->assertSameSize( $expected, $actual );

		// TODO: equality check
		// (simple serialize does not work, since the order is not relevant, and not only on the top level)
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testGetClaims( Entity $entity ) {
		$claims = $entity->getClaims();

		$this->assertInternalType( 'array', $claims );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testGetAllSnaks( Entity $entity ) {
		$snaks = $entity->getAllSnaks();
		$claims = $entity->getClaims();

		$this->assertInternalType( 'array', $snaks );

		$this->assertGreaterThanOrEqual( count( $claims ), count( $snaks ), "At least one snak per Claim" );

		foreach ( $claims as $claim ) {
			$snak = $claim->getMainSnak();
			$this->assertContains( $snak, $snaks, "main snak" );

			$qualifiers = $claim->getQualifiers();

			// check the first qualifier
			foreach ( $qualifiers as $snak ) {
				$this->assertContains( $snak, $snaks, "qualifier snak" );
			}

			// check the first reference
			if ( $claim instanceof Statement ) {
				$references = $claim->getReferences();

				/* @var Reference $ref */
				foreach ( $references as $ref ) {
					$refSnaks = $ref->getSnaks();

					foreach ( $refSnaks as $snak ) {
						$this->assertContains( $snak, $snaks, "reference snak" );
					}
				}
			}
		}
	}

	public function testWhenNoStuffIsSet_getFingerprintReturnsEmptyFingerprint() {
		$entity = $this->getNewEmpty();

		$this->assertEquals(
			Fingerprint::newEmpty(),
			$entity->getFingerprint()
		);
	}

	public function testWhenLabelsAreSet_getFingerprintReturnsFingerprintWithLabels() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setLabel( 'de', 'bar' );

		$this->assertEquals(
			new Fingerprint(
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
				) ),
				new TermList( array() ),
				new AliasGroupList( array() )
			),
			$entity->getFingerprint()
		);
	}

	public function testWhenTermsAreSet_getFingerprintReturnsFingerprintWithTerms() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setDescription( 'en', 'foo bar' );
		$entity->setAliases( 'en', array( 'foo', 'bar' ) );

		$this->assertEquals(
			new Fingerprint(
				new TermList( array(
					new Term( 'en', 'foo' ),
				) ),
				new TermList( array(
					new Term( 'en', 'foo bar' )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			$entity->getFingerprint()
		);
	}

	public function testGivenEmptyFingerprint_noTermsAreSet() {
		$entity = $this->getNewEmpty();
		$entity->setFingerprint( Fingerprint::newEmpty() );

		$this->assertHasNoTerms( $entity );
	}

	private function assertHasNoTerms( Entity $entity ) {
		$this->assertEquals( array(), $entity->getLabels() );
		$this->assertEquals( array(), $entity->getDescriptions() );
		$this->assertEquals( array(), $entity->getAllAliases() );
	}

	public function testGivenEmptyFingerprint_existingTermsAreRemoved() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setDescription( 'en', 'foo bar' );
		$entity->setAliases( 'en', array( 'foo', 'bar' ) );

		$entity->setFingerprint( Fingerprint::newEmpty() );

		$this->assertHasNoTerms( $entity );
	}

	public function testWhenSettingFingerprint_getFingerprintReturnsIt() {
		$fingerprint = new Fingerprint(
			new TermList( array(
				new Term( 'en', 'english label' ),
			) ),
			new TermList( array(
				new Term( 'en', 'english description' )
			) ),
			new AliasGroupList( array(
				new AliasGroup( 'en', array( 'first en alias', 'second en alias' ) )
			) )
		);

		$entity = $this->getNewEmpty();
		$entity->setFingerprint( $fingerprint );
		$newFingerprint = $entity->getFingerprint();

		$this->assertEquals( $fingerprint, $newFingerprint );
	}

}
