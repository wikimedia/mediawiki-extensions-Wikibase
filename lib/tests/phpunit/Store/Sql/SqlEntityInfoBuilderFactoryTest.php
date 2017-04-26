<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilderFactory;

/**
 * @covers Wikibase\Lib\Store\Sql\SqlEntityInfoBuilderFactory
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 */
class SqlEntityInfoBuilderFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return EntityIdComposer
	 */
	private function getIdComposer() {
		return $this->getMockBuilder( EntityIdComposer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		return new EntityNamespaceLookup( [ 'item' => 0, 'property' => 120 ] );
	}

	public function provideInvalidConstructorArguments() {
		return [
			'neither string nor false as database name (int)' => [ 100, '' ],
			'neither string nor false as database name (null)' => [ null, '' ],
			'neither string nor false as database name (true)' => [ true, '' ],
			'not a string as a repository name' => [ false, 1000 ],
			'string containing colon as a repository name' => [ false, 'foo:oo' ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArguments_constructorThrowsException( $databaseName, $repositoryName ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new SqlEntityInfoBuilderFactory(
			new ItemIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup(),
			$databaseName,
			$repositoryName
		);
	}

	public function testNewEntityInfoBuilder_returnsSqlEntityInfoBuilderInstance() {
		$factory = new SqlEntityInfoBuilderFactory(
			new ItemIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup()
		);

		$this->assertInstanceOf( SqlEntityInfoBuilder::class, $factory->newEntityInfoBuilder( [] ) );
	}

	public function testNewEntityInfoBuilder_returnsNewInstanceOnEachCal() {
		$factory = new SqlEntityInfoBuilderFactory(
			new ItemIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup()
		);

		$builderOne = $factory->newEntityInfoBuilder( [] );
		$builderTwo = $factory->newEntityInfoBuilder( [] );

		$this->assertNotSame( $builderOne, $builderTwo );
	}

}
