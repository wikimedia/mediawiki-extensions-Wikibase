<?php

namespace Wikibase\Test;

use Wikibase\content\LabelUniquenessValidator;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\content\LabelUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() ) {
		$matches = array();

		/* @var Term $term */
		foreach ( $terms as $term ) {
			$type = $term->getType();
			$type = $type === null ? $termType : $type;

			if ( $type === 'label' && $term->getText() === 'DUPE' ) {
				$matchTerm = clone $term;
				$matchTerm->setEntityType( Property::ENTITY_TYPE );
				$matchTerm->setNumericId( 666 );

				$matches[] = $matchTerm;
			}
		}

		return $matches;
	}

	/**
	 * @return TermIndex
	 */
	private function getMockTermIndex() {
		$termIndex = $this->getMock( 'Wikibase\TermIndex' );

		$termIndex->expects( $this->any() )
			->method( 'getMatchingTerms' )
			->will( $this->returnCallback( array( $this, 'getMatchingTerms' ) ) );

		return $termIndex;
	}

	public function validEntityProvider() {
		$goodEntity = Property::newFromType( 'string' );
		$goodEntity->setLabel( 'de', 'Foo' );
		$goodEntity->setDescription( 'de', 'DUPE' );
		$goodEntity->setId( new PropertyId( 'P5' ) );

		return array(
			array( $goodEntity ),
		);
	}

	public function invalidEntityProvider() {
		$badEntity = Property::newFromType( 'string' );
		$badEntity->setLabel( 'de', 'DUPE' );
		$badEntity->setId( new PropertyId( 'P7' ) );

		return array(
			array( $badEntity, 'wikibase-error-label-not-unique-wikibase-property' ),
		);
	}

	/**
	 * @dataProvider validEntityProvider
	 *
	 * @param Entity $entity
	 */
	public function testValidateEntity( Entity $entity ) {
		$termIndex = $this->getMockTermIndex();
		$validator = new LabelUniquenessValidator( $termIndex );

		$status = $validator->validateEntity( $entity );

		$this->assertTrue( $status->isOK(), 'isOK' );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 *
	 * @param Entity $entity
	 * @param string|null $error
	 */
	public function testValidateEntity_failure( Entity $entity, $error ) {
		$termIndex = $this->getMockTermIndex();
		$validator = new LabelUniquenessValidator( $termIndex );

		$status = $validator->validateEntity( $entity );

		$this->assertFalse( $status->isOK(), 'isOK' );

		$errors = $status->getErrorsArray();
		$this->assertEquals( $error, $errors[0][0] );
	}

}
