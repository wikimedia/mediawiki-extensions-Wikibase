<?php

namespace Wikibase\Repo\Tests\Hooks;

use IContextSource;
use OutputPage;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;

/**
 * @covers Wikibase\Repo\Hooks\OutputPageEntityIdReader
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageEntityIdReaderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getEntityIdFromOutputPageProvider
	 */
	public function testGetEntityIdFromOutputPage( $expected, OutputPage $out, $isEntityContentModel ) {
		$entityContentFactory = $this->getMockBuilder( EntityContentFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$entityContentFactory->expects( $this->once() )
			->method( 'isEntityContentModel' )
			->with( 'bar' )
			->will( $this->returnValue( $isEntityContentModel ) );

		$outputPageEntityIdReader = new OutputPageEntityIdReader(
			$entityContentFactory,
			new ItemIdParser()
		);

		$this->assertEquals(
			$expected,
			$outputPageEntityIdReader->getEntityIdFromOutputPage( $out )
		);
	}

	public function getEntityIdFromOutputPageProvider() {
		$title = $this->getMock( Title::class );
		$title->expects( $this->any() )
			->method( 'getContentModel' )
			->will( $this->returnValue( 'bar' ) );

		$context = $this->getMock( IContextSource::class );
		$context->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage = new OutputPage( $context );
		$outputPageEntityId = clone $outputPage;
		$outputPageEntityId->addJsConfigVars( 'wbEntityId', 'Q42' );

		return [
			'Entity id set' => [
				new ItemId( 'Q42' ),
				$outputPageEntityId,
				true
			],
			'entity content model, but no entity id set' => [
				null,
				$outputPage,
				true
			],
			'no entity content model, should abort early' => [
				null,
				$outputPageEntityId,
				false
			],
		];
	}

}
