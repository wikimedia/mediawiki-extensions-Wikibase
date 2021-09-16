<?php

namespace Wikibase\Lib\Tests\Formatters\Reference;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TestLogger;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Formatters\Reference\WellKnownReferenceProperties;

/**
 * @covers \Wikibase\Lib\Formatters\Reference\WellKnownReferencePropertiesTest
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WellKnownReferencePropertiesTest extends TestCase {

	/** A logger that fails the test if anything is logged. */
	private function neverCalledLogger(): LoggerInterface {
		$logger = $this->getMockForAbstractClass( AbstractLogger::class );
		$logger->expects( $this->never() )->method( 'log' );
		return $logger;
	}

	public function testNewFromArray_allNull() {
		$properties = WellKnownReferenceProperties::newFromArray( [
			'referenceUrl' => null,
			'title' => null,
			'statedIn' => null,
			'author' => null,
			'publisher' => null,
			'publicationDate' => null,
			'retrievedDate' => null,
		], $this->neverCalledLogger() );

		$this->assertNull( $properties->referenceUrlPropertyId );
		$this->assertNull( $properties->titlePropertyId );
		$this->assertNull( $properties->statedInPropertyId );
		$this->assertNull( $properties->authorPropertyId );
		$this->assertNull( $properties->publisherPropertyId );
		$this->assertNull( $properties->publicationDatePropertyId );
		$this->assertNull( $properties->retrievedDatePropertyId );
	}

	public function testNewFromArray_complete() {
		$referenceUrlPropertyId = new NumericPropertyId( 'P1' );
		$titlePropertyId = new NumericPropertyId( 'P2' );
		$statedInPropertyId = new NumericPropertyId( 'P3' );
		$authorPropertyId = new NumericPropertyId( 'P4' );
		$publisherPropertyId = new NumericPropertyId( 'P5' );
		$publicationDatePropertyId = new NumericPropertyId( 'P6' );
		$retrievedDatePropertyId = new NumericPropertyId( 'P7' );

		$properties = WellKnownReferenceProperties::newFromArray( [
			'referenceUrl' => $referenceUrlPropertyId->getSerialization(),
			'title' => $titlePropertyId->getSerialization(),
			'statedIn' => $statedInPropertyId->getSerialization(),
			'author' => $authorPropertyId->getSerialization(),
			'publisher' => $publisherPropertyId->getSerialization(),
			'publicationDate' => $publicationDatePropertyId->getSerialization(),
			'retrievedDate' => $retrievedDatePropertyId->getSerialization(),
		], $this->neverCalledLogger() );

		$this->assertEquals( $referenceUrlPropertyId, $properties->referenceUrlPropertyId );
		$this->assertEquals( $titlePropertyId, $properties->titlePropertyId );
		$this->assertEquals( $statedInPropertyId, $properties->statedInPropertyId );
		$this->assertEquals( $authorPropertyId, $properties->authorPropertyId );
		$this->assertEquals( $publisherPropertyId, $properties->publisherPropertyId );
		$this->assertEquals( $publicationDatePropertyId, $properties->publicationDatePropertyId );
		$this->assertEquals( $retrievedDatePropertyId, $properties->retrievedDatePropertyId );
	}

	public function testNewFromArray_empty() {
		$logger = new TestLogger( true );

		$properties = WellKnownReferenceProperties::newFromArray( [], $logger );

		$this->assertNull( $properties->referenceUrlPropertyId );
		$this->assertNull( $properties->titlePropertyId );
		$this->assertNull( $properties->statedInPropertyId );
		$this->assertNull( $properties->authorPropertyId );
		$this->assertNull( $properties->publisherPropertyId );
		$this->assertNull( $properties->publicationDatePropertyId );
		$this->assertNull( $properties->retrievedDatePropertyId );

		$buffer = $logger->getBuffer();
		$this->assertCount( 7, $buffer );
		foreach ( $buffer as $entry ) {
			$this->assertSame( LogLevel::INFO, $entry[0] );
		}
	}

	public function testNewFromArray_unknownKey() {
		$logger = new TestLogger( true );

		WellKnownReferenceProperties::newFromArray( [
			'referenceUrl' => null,
			'title' => null,
			'statedIn' => null,
			'author' => null,
			'publisher' => null,
			'publicationDate' => null,
			'retrievedDate' => null,
			'unknown key' => 'P1',
		], $logger );

		$buffer = $logger->getBuffer();
		$this->assertCount( 1, $buffer );
		$this->assertSame( LogLevel::WARNING, $buffer[0][0] );
	}

	public function testNewFromArray_invalidValue() {
		$logger = new TestLogger( true );

		$properties = WellKnownReferenceProperties::newFromArray( [
			'referenceUrl' => 'P0',
			'title' => null,
			'statedIn' => null,
			'author' => null,
			'publisher' => null,
			'publicationDate' => null,
			'retrievedDate' => null,
		], $logger );

		$buffer = $logger->getBuffer();
		$this->assertCount( 1, $buffer );
		$this->assertSame( LogLevel::ERROR, $buffer[0][0] );
		$this->assertNull( $properties->referenceUrlPropertyId );
	}

}
