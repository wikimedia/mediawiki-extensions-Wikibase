<?php

namespace Wikibase\Repo\Tests\Rdf;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Rdf\DispatchingValueSnakRdfBuilder;
use Wikibase\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\DispatchingValueSnakRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DispatchingValueSnakRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testAddValue() {
		$writer = $this->getMock( RdfWriter::class );
		$namespace = 'xx';
		$lname = 'yy';

		$propertyId = new PropertyId( 'P123' );
		$snak = new PropertyValueSnak( $propertyId, new StringValue( 'xyz' ) );

		$ptBuilder = $this->getMock( ValueSnakRdfBuilder::class );
		$ptBuilder->expects( $this->once() )
			->method( 'addValue' )
			->with( $writer, $namespace, $lname, 'foo', $snak );

		$vtBuilder = $this->getMock( ValueSnakRdfBuilder::class );
		$vtBuilder->expects( $this->once() )
			->method( 'addValue' )
			->with( $writer, $namespace, $lname, 'bar', $snak );

		$dispatchingBuilder = new DispatchingValueSnakRdfBuilder( [
			'PT:foo' => $ptBuilder,
			'VT:string' => $vtBuilder
		] );

		$dispatchingBuilder->addValue( $writer, $namespace, $lname, 'foo', $snak );
		$dispatchingBuilder->addValue( $writer, $namespace, $lname, 'bar', $snak );
	}

}
