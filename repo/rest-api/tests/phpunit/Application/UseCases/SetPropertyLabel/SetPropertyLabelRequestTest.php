<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetPropertyLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabelRequest;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabelRequest
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class SetPropertyLabelRequestTest extends TestCase {

	public function testNew(): void {
		$propertyId = 'P123';
		$langCode = 'en';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;

		$request = new SetPropertyLabelRequest( $propertyId, $langCode, $newLabelText, $editTags, $isBot, null, null );
		$this->assertEquals( $propertyId, $request->getPropertyId() );
		$this->assertEquals( $langCode, $request->getLanguageCode() );
		$this->assertEquals( $newLabelText, $request->getLabel() );
		$this->assertEquals( $editTags, $request->getEditTags() );
		$this->assertFalse( $request->isBot() );
	}

	public function testNewWithTrimmedLabel(): void {
		$propertyId = 'P123';
		$langCode = 'en';
		$spaceyLabelText = '     New label     ';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;

		$request = new SetPropertyLabelRequest( $propertyId, $langCode, $spaceyLabelText, $editTags, $isBot, null, null );
		$this->assertEquals( $propertyId, $request->getPropertyId() );
		$this->assertEquals( $langCode, $request->getLanguageCode() );
		$this->assertEquals( 'New label', $request->getLabel() );
		$this->assertEquals( $editTags, $request->getEditTags() );
		$this->assertFalse( $request->isBot() );
	}

}
