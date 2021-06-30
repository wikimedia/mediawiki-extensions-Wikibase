<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\FederatedProperties\BaseUriExtractor;

/**
 * @covers \Wikibase\Repo\FederatedProperties\BaseUriExtractor
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class BaseUriExtractorTest extends TestCase {

	public function testExtractBaseUriFromValidSerialization() {
		$extractor = new BaseUriExtractor();
		$propertyId = 'P12';
		$expectedBaseUri = 'http://www.wikdiata.org/entity/';
		$serialization = $expectedBaseUri . $propertyId;
		$baseUri = $extractor->getBaseUriFromSerialization( $serialization );
		$this->assertEquals( $expectedBaseUri, $baseUri );
	}

	/**
	 * @dataProvider provideNonWikibaseUris
	 */
	public function testThrowsIfUriIsValidButDoesNotLookLikeWikibaseConceptUri( $serialization ) {
		$extractor = new BaseUriExtractor();
		$this->expectException( EntityIdParsingException::class );
		$baseUri = $extractor->getBaseUriFromSerialization( $serialization );
	}

	public function provideNonWikibaseUris() {
	return [
		'Has a Query' => [ 'http://www.wikdiata.org/entity/P12?action=catdog' ],
		'Has a Fragment' => [ 'http://www.wikdiata.org/entity/P12#catdog' ],
		'Has no Path' => [ 'http://www.wikdiata.org' ],
		'Has malformed Path' => [ 'http://www.wikdiata.org/entity###;\'\';l\'' ],
	];
	}

}
