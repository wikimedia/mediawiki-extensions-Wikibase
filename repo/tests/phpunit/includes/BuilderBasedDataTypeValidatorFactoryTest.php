<?php

namespace Wikibase\Repo\Tests;

use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikimedia\Assert\ParameterElementTypeException;
use Wikimedia\Assert\PostconditionException;

/**
 * @covers Wikibase\Repo\BuilderBasedDataTypeValidatorFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class BuilderBasedDataTypeValidatorFactoryTest extends PHPUnit_Framework_TestCase {

	public function testInvalidConstructorArgument() {
		$this->setExpectedException( ParameterElementTypeException::class );
		new BuilderBasedDataTypeValidatorFactory( array( 'invalid' ) );
	}

	public function testUnknownPropertyType() {
		$factory = new BuilderBasedDataTypeValidatorFactory( array() );
		$this->setExpectedException( OutOfBoundsException::class );
		$factory->getValidators( 'unknown' );
	}

	public function testInvalidValidatorsArray() {
		$factory = new BuilderBasedDataTypeValidatorFactory( array( 'id' => function() {
			return 'invalid';
		} ) );
		$this->setExpectedException( PostconditionException::class );
		$factory->getValidators( 'id' );
	}

	public function testEmptyValidatorsArray() {
		$factory = new BuilderBasedDataTypeValidatorFactory( array( 'id' => function() {
			return array();
		} ) );
		$this->assertSame( array(), $factory->getValidators( 'id' ) );
	}

	public function testInvalidValidatorObject() {
		$factory = new BuilderBasedDataTypeValidatorFactory( array( 'id' => function() {
			return array( 'invalid' );
		} ) );
		$this->setExpectedException( PostconditionException::class );
		$factory->getValidators( 'id' );
	}

	public function testGetValidators() {
		$validators = [ new CompositeValidator( [] ) ];
		$factory = new BuilderBasedDataTypeValidatorFactory( array(
			'id' => function() use ( $validators ) {
				return $validators;
			},
		) );
		$this->assertSame( $validators, $factory->getValidators( 'id' ) );
	}

}
