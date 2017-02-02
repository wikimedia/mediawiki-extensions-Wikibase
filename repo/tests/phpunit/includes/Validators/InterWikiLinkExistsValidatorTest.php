<?php

namespace Wikibase\Repo\Tests\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\Validators\CommonsMediaExistsValidator;
use Wikibase\Repo\Validators\InterWikiLinkExistsValidator;
use MediaWiki\Site\MediaWikiPageNameNormalizer;

/**
 * @covers Wikibase\Repo\Validators\InterWikiLinkExistsValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Jonas Kress
 */
class InterWikiLinkExistsValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return MediaWikiPageNameNormalizer
	 */
	private function getMediaWikiPageNameNormalizer() {
		$pageNormalizer = $this->getMockBuilder( MediaWikiPageNameNormalizer::class )
		->disableOriginalConstructor()
		->getMock();

		$pageNormalizer->expects( $this->any() )
		->method( 'normalizePageName' )
		->with( $this->isType( 'string' ), '[COMMONS_API_URL]' )
		->will( $this->returnCallback( function( $pageName ) {
			return strpos( $pageName, 'NOT-FOUND' ) === false ? $pageName : false;
		} ) );

			return $pageNormalizer;
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $expected, $value ) {
		$validator = new InterWikiLinkExistsValidator( $this->getMediaWikiPageNameNormalizer(), '[COMMONS_API_URL]' );

		$this->assertSame(
			$expected,
			$validator->validate( $value )->isValid()
		);
	}

	public function provideValidate() {
		return array(
			"Valid, plain string" => array(
				true, "Foo.png"
			),
			"Valid, StringValue" => array(
				true, new StringValue( "Foo.png" )
			),
			"Invalid, StringValue" => array(
				false, new StringValue( "Foo.NOT-FOUND.png" )
			)
		);
	}

	public function testValidate_noString() {
		$validator = new InterWikiLinkExistsValidator( $this->getMediaWikiPageNameNormalizer(), null );

		$this->setExpectedException( InvalidArgumentException::class );
		$validator->validate( 5 );
	}

}
