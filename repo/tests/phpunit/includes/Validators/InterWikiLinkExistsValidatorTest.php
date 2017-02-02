<?php

namespace Wikibase\Repo\Tests\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Repo\Validators\InterWikiLinkExistsValidator;
use MediaWiki\Site\MediaWikiPageNameNormalizer;

/**
 * @covers Wikibase\Repo\Validators\InterWikiLinkExistsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Jonas Kress
 */
class InterWikiLinkExistsValidatorTest extends \PHPUnit_Framework_TestCase {

	const EXISTING_FILE = "Foo.png";
	const NONEXISTENT_FILE = "Foo.NOT-FOUND.png";

	/**
	 * @return MediaWikiPageNameNormalizer
	 */
	private function getMediaWikiPageNameNormalizer() {
		$pageNormalizer = $this->getMockBuilder( MediaWikiPageNameNormalizer::class )
			->disableOriginalConstructor()
			->getMock();

		$pageNormalizer->method( 'normalizePageName' )
			->with( $this->isType( 'string' ), $this->anything() )
			->will( $this->returnCallback( function( $pageName ) {
				return $pageName === self::EXISTING_FILE ? $pageName : false;
			} ) );

		return $pageNormalizer;
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $expected, $value ) {
		$validator = new InterWikiLinkExistsValidator(
			$this->getMediaWikiPageNameNormalizer(),
			'http://does-not.matter'
		);

		$this->assertSame(
			$expected,
			$validator->validate( $value )->isValid()
		);
	}

	public function provideValidate() {
		return [
			"Valid, plain string" => [
				true, self::EXISTING_FILE
			],
			"Valid, StringValue" => [
				true, new StringValue( self::EXISTING_FILE )
			],
			"Invalid, StringValue" => [
				false, new StringValue( self::NONEXISTENT_FILE )
			]
		];
	}

	public function testValidate_noString() {
		$validator = new InterWikiLinkExistsValidator(
			$this->getMediaWikiPageNameNormalizer(),
			'http://does-not.matter/'
		);

		$this->setExpectedException( InvalidArgumentException::class );
		$validator->validate( 5 );
	}

}
