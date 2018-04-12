<?php

namespace Wikibase\Repo\Tests;

use OutOfBoundsException;
use PHPUnit4And6Compat;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikimedia\Assert\ParameterElementTypeException;
use Wikimedia\Assert\PostconditionException;

/**
 * @covers Wikibase\Repo\BuilderBasedDataTypeValidatorFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class BuilderBasedDataTypeValidatorFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testInvalidConstructorArgument() {
		$this->setExpectedException( ParameterElementTypeException::class );
		new BuilderBasedDataTypeValidatorFactory( [ 'invalid' ] );
	}

	public function testUnknownPropertyType() {
		$factory = new BuilderBasedDataTypeValidatorFactory( [] );
		$this->setExpectedException( OutOfBoundsException::class );
		$factory->getValidators( 'unknown' );
	}

	public function testInvalidValidatorsArray() {
		$factory = new BuilderBasedDataTypeValidatorFactory( [ 'id' => function() {
			return 'invalid';
		} ] );
		$this->setExpectedException( PostconditionException::class );
		$factory->getValidators( 'id' );
	}

	public function testEmptyValidatorsArray() {
		$factory = new BuilderBasedDataTypeValidatorFactory( [ 'id' => function() {
			return [];
		} ] );
		$this->assertSame( [], $factory->getValidators( 'id' ) );
	}

	public function testInvalidValidatorObject() {
		$factory = new BuilderBasedDataTypeValidatorFactory( [ 'id' => function() {
			return [ 'invalid' ];
		} ] );
		$this->setExpectedException( PostconditionException::class );
		$factory->getValidators( 'id' );
	}

	public function testGetValidators() {
		$validators = [ new CompositeValidator( [] ) ];
		$factory = new BuilderBasedDataTypeValidatorFactory( [
			'id' => function() use ( $validators ) {
				return $validators;
			},
		] );
		$this->assertSame( $validators, $factory->getValidators( 'id' ) );
	}

}
