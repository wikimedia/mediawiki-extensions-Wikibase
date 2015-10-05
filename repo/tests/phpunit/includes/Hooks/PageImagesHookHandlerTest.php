<?php

namespace Wikibase\Repo\Tests\Hooks;

use DataValues\BooleanValue;
use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\Hooks\PageImagesHookHandler;

/**
 * @covers Wikibase\Repo\Hooks\PageImagesHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Thiemo Mättig
 */
class PageImagesHookHandlerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param string[] $propertyIds
	 *
	 * @return PageImagesHookHandler
	 */
	private function getHandler( array $propertyIds ) {
		return new PageImagesHookHandler( $propertyIds );
	}

	/**
	 * @param StatementList $statements
	 * @param int $propertyId
	 * @param string $fileName
	 * @param int $rank
	 */
	private function addStatement(
		StatementList $statements,
		$propertyId,
		$fileName,
		$rank = Statement::RANK_NORMAL
	) {
		$statement = new Statement(
			new PropertyValueSnak( $propertyId, new StringValue( $fileName ) )
		);
		$statement->setRank( $rank );
		$statements->addStatement( $statement );
	}

	/**
	 * @dataProvider bestImageProvider
	 */
	public function testGetBestImageFileName(
		StatementList $statements,
		array $propertyIds,
		$expected
	) {
		$handler = $this->getHandler( $propertyIds );
		$this->assertSame( $expected, $handler->getBestImageFileName( $statements ) );
	}

	public function bestImageProvider() {
		$statements = new StatementList();

		$this->addStatement( $statements, 1, '1.jpg' );

		$statements->addNewStatement( new PropertyNoValueSnak( 2 ) );
		$statements->addNewStatement( new PropertySomeValueSnak( 2 ) );
		$statements->addNewStatement( new PropertyValueSnak( 2, new BooleanValue( true ) ) );
		$this->addStatement( $statements, 2, '2.jpg', Statement::RANK_DEPRECATED );

		$statements->addNewStatement( new PropertySomeValueSnak( 3 ) );
		$this->addStatement( $statements, 3, '3a.jpg' );
		$this->addStatement( $statements, 3, '3b.jpg' );

		$this->addStatement( $statements, 4, '4a.jpg', Statement::RANK_DEPRECATED );
		$this->addStatement( $statements, 4, '4b.jpg' );
		$this->addStatement( $statements, 4, '4c.jpg' );

		$this->addStatement( $statements, 5, '5a.jpg' );
		$this->addStatement( $statements, 4, '5b.jpg', Statement::RANK_DEPRECATED );
		$this->addStatement( $statements, 5, '5c.jpg', Statement::RANK_PREFERRED );
		$this->addStatement( $statements, 5, '5d.jpg', Statement::RANK_PREFERRED );

		return array(
			// Find nothing for various reasons.
			'Ignore non-strings' => array( $statements, array( 'P2' ), null ),
			'Property not found' => array( $statements, array( 'P9999' ), null ),
			'Not a property id' => array( $statements, array( 'Q1' ), null ),
			'Invalid id' => array( $statements, array( 'invalid' ), null ),

			// Simple searches.
			'Find 1' => array( $statements, array( 'P1' ), '1.jpg' ),
			'Skip non-strings' => array( $statements, array( 'P3' ), '3a.jpg' ),
			'Skip missing ids' => array( $statements, array( 'P9999', 'P1' ), '1.jpg' ),
			'Skip item ids' => array( $statements, array( 'Q1', 'P1' ), '1.jpg' ),
			'Skip invalid ids' => array( $statements, array( 'invalid', 'P1' ), '1.jpg' ),

			'Increasing order' => array( $statements, array( 'P1', 'P2', 'P3' ), '1.jpg' ),
			'Decreasing order' => array( $statements, array( 'P3', 'P2', 'P1' ), '3a.jpg' ),

			'Skip deprecated' => array( $statements, array( 'P4' ), '4b.jpg' ),
			'Prefer preferred' => array( $statements, array( 'P5' ), '5c.jpg' ),
		);
	}

}
