<?php

namespace Wikibase\Client\Tests\Integration\Api;

use ApiMain;
use ApiUsageException;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use FauxRequest;
use MediaWikiIntegrationTestCase;
use Parser;
use ParserOutput;
use Wikibase\Client\Api\ApiFormatReference;
use Wikibase\Client\DataAccess\ReferenceFormatterFactory;
use Wikibase\DataModel\Reference;
use Wikibase\Lib\Formatters\Reference\DataBridgeReferenceFormatter;
use Wikibase\Lib\Formatters\Reference\ReferenceFormatter;

/**
 * Unit tests for wbformatreference,
 * injecting custom services and calling the class directly.
 * Still ran as an integration test (Database group, {@link MediaWikiIntegrationTestCase}),
 * because the API may include behavior that requires a database,
 * including from other extensions (e.g. T310255).
 * See also {@link ApiFormatReferenceTest}.
 *
 * @covers \Wikibase\Client\Api\ApiFormatReference
 *
 * @group Database
 * @group API
 * @group Wikibase
 * @group WikibaseApi
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 */
class ApiFormatReferenceUnitTest extends MediaWikiIntegrationTestCase {

	private function getApiMain( array $params ): ApiMain {
		return new ApiMain( new FauxRequest( $params ) );
	}

	/** A reference deserializer that expects the given input and returns the given output. */
	private function getReferenceDeserializer( array $inputJson, Reference $outputReference ): Deserializer {
		$referenceDeserializer = $this->createMock( Deserializer::class );
		$referenceDeserializer->method( 'deserialize' )
			->with( $inputJson )
			->willReturn( $outputReference );
		return $referenceDeserializer;
	}

	/** A reference formatter that expects the given input and returns the given output. */
	private function getReferenceFormatter( Reference $inputReference, string $outputWikitext ): ReferenceFormatter {
		$referenceFormatter = $this->createMock( DataBridgeReferenceFormatter::class );
		$referenceFormatter->method( 'formatReference' )
			->with( $this->identicalTo( $inputReference ) )
			->willReturn( $outputWikitext );
		return $referenceFormatter;
	}

	/** A reference formatter factory that returns the given formatter. */
	private function getReferenceFormatterFactory( ReferenceFormatter $dataBridgeReferenceFormatter ): ReferenceFormatterFactory {
		$referenceFormatterFactory = $this->createMock( ReferenceFormatterFactory::class );
		$referenceFormatterFactory->method( 'newDataBridgeReferenceFormatter' )
			->willReturn( $dataBridgeReferenceFormatter );
		return $referenceFormatterFactory;
	}

	/** A parser that expects the given input and returns the given output. */
	private function getParser( string $inputWikitext, string $outputHtml ): Parser {
		$parser = $this->createMock( Parser::class );
		$parser->method( 'parse' )
			->with( $inputWikitext );
		$parser->method( 'getOutput' )
			->willReturn( new ParserOutput( $outputHtml ) );
		return $parser;
	}

	/** A reference deserializer that expects not to be called. */
	private function unusedReferenceDeserializer(): Deserializer {
		$referenceDeserializer = $this->createMock( Deserializer::class );
		$referenceDeserializer->expects( $this->never() )->method( 'deserialize' );
		return $referenceDeserializer;
	}

	/** A reference formatter factory that expects not to be called. */
	private function unusedReferenceFormatterFactory(): ReferenceFormatterFactory {
		$referenceFormatterFactory = $this->createMock( ReferenceFormatterFactory::class );
		$referenceFormatterFactory->expects( $this->never() )->method( 'newDataBridgeReferenceFormatter' );
		return $referenceFormatterFactory;
	}

	/** A parser that expects not to be called. */
	private function unusedParser(): Parser {
		$parser = $this->createMock( Parser::class );
		$parser->expects( $this->never() )->method( 'parse' );
		$parser->expects( $this->never() )->method( 'getOutput' );
		return $parser;
	}

	public function testAsDataBridgeForHtml() {
		$json = [ 'input JSON' ];
		$reference = $this->createMock( Reference::class );
		$wikitext = 'output wikitext';
		$html = 'output HTML';

		$referenceDeserializer = $this->getReferenceDeserializer( $json, $reference );
		$referenceFormatter = $this->getReferenceFormatter( $reference, $wikitext );
		$referenceFormatterFactory = $this->getReferenceFormatterFactory( $referenceFormatter );
		$parser = $this->getParser( $wikitext, $html );

		$params = [
			'action' => 'wbformatreference',
			'reference' => json_encode( $json ),
			'style' => 'internal-data-bridge',
			'outputformat' => 'html',
		];
		$module = new ApiFormatReference(
			$this->getApiMain( $params ),
			'wbformatreference',
			$parser,
			$referenceFormatterFactory,
			$referenceDeserializer
		);

		$module->execute();

		$result = $module->getResult();
		$data = $result->getResultData( null, [ 'Strip' => 'all' ] );
		$this->assertSame( [ 'wbformatreference' => [ 'html' => $html ] ], $data );
	}

	public function testInvalidReferenceJson() {
		$params = [
			'action' => 'wbformatreference',
			'reference' => '"invalid JSON (no closing quotation mark)',
			'style' => 'internal-data-bridge',
			'outputformat' => 'html',
		];
		$module = new ApiFormatReference(
			$this->getApiMain( $params ),
			'wbformatreference',
			$this->unusedParser(),
			$this->unusedReferenceFormatterFactory(),
			$this->unusedReferenceDeserializer()
		);

		$this->expectException( ApiUsageException::class );
		$module->execute();
	}

	public function testInvalidReferenceSerialization() {
		$inputJson = 'invalid serialization';
		$referenceDeserializer = $this->createMock( Deserializer::class );
		$referenceDeserializer->method( 'deserialize' )
			->with( $inputJson )
			->willThrowException( new DeserializationException() );

		$params = [
			'action' => 'wbformatreference',
			'reference' => json_encode( $inputJson ),
			'style' => 'internal-data-bridge',
			'outputformat' => 'html',
		];
		$module = new ApiFormatReference(
			$this->getApiMain( $params ),
			'wbformatreference',
			$this->unusedParser(),
			$this->unusedReferenceFormatterFactory(),
			$referenceDeserializer
		);

		$this->expectException( ApiUsageException::class );
		$module->execute();
	}

}
