<?php

namespace Wikibase\Test\Repo\Validators;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\Validators\LabelUniquenessValidator;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers Wikibase\Repo\Validators\LabelUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LabelUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	private function getMockDupeDetector() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockLabelDescriptionDuplicateDetector();
	}

	public function validFingerprintProvider() {
		$p99 = new PropertyId( 'P99' );

		return array(
			'no conflict' => array(
				new Fingerprint(
					new TermList( array( new Term( 'de', 'Foo' ) ) ),
					new TermList(),
					new AliasGroupList()
				),
				$p99
			),
			'self conflict' => array(
				// the mock considers "DUPE" a dupe with P666
				new Fingerprint(
					new TermList( array( new Term( 'de', 'DUPE' ) ) ),
					new TermList(),
					new AliasGroupList()
				),
				new PropertyId( 'P666' ) // ignore conflicts with P666
			),
			'ignored conflict' => array(
				// the mock considers "DUPE" a dupe with P666
				new Fingerprint(
					new TermList( array( new Term( 'de', 'DUPE' ) ) ),
					new TermList(),
					new AliasGroupList()
				),
				$p99,
				array( 'en' ) // only consider conflicts in english
			),
		);
	}

	private function fingerprintCaseToEntityCase( $fingerprintCase, $id ) {
		$fingerprint = reset( $fingerprintCase );

		$item = Property::newFromType( 'string' );
		$item->setFingerprint( $fingerprint );
		$item->setId( $id );

		$entityCase = $fingerprintCase;
		$entityCase[0] = $item;

		return $entityCase;
	}

	public function validEntityProvider() {
		$cases = array();

		foreach ( $this->validFingerprintProvider() as $name => $fingerprintCase ) {
			// if the case has a non-null languageCodes or a strange entityId param, skip it
			if ( isset( $fingerprintCase[2] ) || !( $fingerprintCase[1] instanceof PropertyId ) ) {
				continue;
			}

			$id = $fingerprintCase[1];
			$cases[$name] = $this->fingerprintCaseToEntityCase( $fingerprintCase, $id );
		}

		// check validation without entity id
		$cases["no id"] = array(
			Property::newFromType( 'string' ),
		);

		return $cases;
	}

	public function invalidFingerprintProvider() {
		$dupeLabelFingerprint = new Fingerprint(
			new TermList( array( new Term( 'de', 'DUPE' ) ) ),
			new TermList(),
			new AliasGroupList()
		);

//		$dupeAliasFingerprint = new Fingerprint(
//			new TermList( array( new Term( 'de', 'good' ) ) ),
//			new TermList(),
//			new AliasGroupList( array( new AliasGroup( 'de', array( 'DUPE' ) ) ) )
//		);

		return array(
			'conflicting label' => array( $dupeLabelFingerprint, 'label-conflict' ),
			// insert again when T104393 is resolved
			// 'conflicting alias' => array( $dupeAliasFingerprint, 'label-conflict' ),
		);
	}

	public function invalidEntityProvider() {
		$cases = array();

		$i = 1;
		foreach ( $this->invalidFingerprintProvider() as $name => $fingerprintCase ) {
			$id = new PropertyId( 'P' . $i++ );
			$cases[$name] = $this->fingerprintCaseToEntityCase( $fingerprintCase, $id );
		}

		return $cases;
	}

	/**
	 * @dataProvider validEntityProvider
	 *
	 * @param EntityDocument $entity
	 */
	public function testValidateEntity( EntityDocument $entity ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/**
	 * @dataProvider validFingerprintProvider
	 *
	 * @param Fingerprint $fingerprint
	 * @param EntityId $entityId
	 * @param string[]|null $languageCodes
	 */
	public function testValidateFingerprint(
		Fingerprint $fingerprint,
		EntityId $entityId,
		$languageCodes = null
	) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint( $fingerprint, $entityId, $languageCodes );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 *
	 * @param EntityDocument $entity
	 * @param string|null $error
	 */
	public function testValidateEntity_failure( EntityDocument $entity, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

	/**
	 * @dataProvider invalidFingerprintProvider
	 *
	 * @param Fingerprint $fingerprint
	 * @param string|null $error
	 */
	public function testValidateFingerprint_failure( Fingerprint $fingerprint, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint( $fingerprint, new PropertyId( 'P99' ) );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

}
