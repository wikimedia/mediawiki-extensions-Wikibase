<?php

/**
 * @covers MessageException
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @todo move to MediaWiki core
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageExceptionTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getMwMessageProvider
	 */
	public function testGetMwMessage( $expected, $message, $exceptionMsg ) {
		$exception = new MessageException( $message, $exceptionMsg );

		$this->assertInstanceOf( 'MessageException', $exception );
		$this->assertEquals( $expected, $exception->getMessage() );
	}

	public function getMwMessageProvider() {
		$message = $this->getMockBuilder( 'Message' )
			->setConstructorArgs( array( 'foo', array( 'bar' ), Language::factory( 'de' ) ) )
			->setMethods( array( 'fetchMessage' ) )
			->getMock();

		$message->expects( $this->any() )
			->method( 'fetchMessage' )
			->will( $this->returnCallback( function() use ( $message ) {
				$cache = array(
					'de' => 'Katzen',
					'en' => 'Cat',
					'es' => 'Gato'
				);

				$langCode = $message->getLanguageCode();
				return $cache[$langCode];
			} ) );

		return array(
			array( 'Unexpected error', $message, 'Unexpected error' ),
			array( 'Cat', $message, '' )
		);
	}

}
