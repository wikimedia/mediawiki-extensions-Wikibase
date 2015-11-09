<?php

namespace Wikibase\Test\Rdf;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Rdf\DispatchingValueSnakRdfBuilder;

/**
 * @covers Wikibase\Rdf\DispatchingValueSnakRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchingValueSnakRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testAddValue() {
		$writer = $this->getMock( 'Wikimedia\Purtle\RdfWriter' );
		$namespace = 'xx';
		$lname = 'yy';

		$propertyId = new PropertyId( 'P123' );
		$snak = new PropertyValueSnak( $propertyId, new StringValue( 'xyz' ) );

		$ptBuilder = $this->getMock( 'Wikibase\Rdf\ValueSnakRdfBuilder' );
		$ptBuilder->expects( $this->once() )
			->method( 'addValue' )
			->with( $writer, $namespace, $lname, 'foo', $snak );

		$vtBuilder = $this->getMock( 'Wikibase\Rdf\ValueSnakRdfBuilder' );
		$vtBuilder->expects( $this->once() )
			->method( 'addValue' )
			->with( $writer, $namespace, $lname, 'bar', $snak );

		$dispatchingBuilder = new DispatchingValueSnakRdfBuilder( array(
			'PT:foo' => $ptBuilder,
			'VT:string' => $vtBuilder
		) );

		$dispatchingBuilder->addValue( $writer, $namespace, $lname, 'foo', $snak );
		$dispatchingBuilder->addValue( $writer, $namespace, $lname, 'bar', $snak );
	}

}
