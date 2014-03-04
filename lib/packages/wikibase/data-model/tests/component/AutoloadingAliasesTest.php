<?php

namespace Tests\Wikibase\DataModel;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AutoloadingAliasesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider oldNameProvider
	 */
	public function testAliasExists( $className ) {
		$this->assertTrue(
			class_exists( $className ) || interface_exists( $className ),
			'Class name "' . $className . '" should still exist as alias'
		);
	}

	public function oldNameProvider() {
		return array_map(
			function( $className ) {
				return array( $className );
			},
			array(
				'Wikibase\EntityId',
				'Wikibase\ItemObject',
				'Wikibase\ReferenceObject',
				'Wikibase\StatementObject',
				'Wikibase\ClaimObject',

				'Wikibase\Reference',
				'Wikibase\ReferenceList',
				'Wikibase\References',
				'Wikibase\HashableObjectStorage',
				'Wikibase\HashArray',
				'Wikibase\MapHasher',
				'Wikibase\MapValueHasher',
				'Wikibase\ByPropertyIdArray',
				'Wikibase\Claim',
				'Wikibase\ClaimAggregate',
				'Wikibase\ClaimListAccess',
				'Wikibase\Claims',
				'Wikibase\Statement',
				'Wikibase\Entity',
				'Wikibase\Item',
				'Wikibase\Property',
				'Wikibase\PropertyNoValueSnak',
				'Wikibase\PropertySomeValueSnak',
				'Wikibase\PropertyValueSnak',
				'Wikibase\Snak',
				'Wikibase\SnakList',
				'Wikibase\SnakObject',
				'Wikibase\SnakRole',
				'Wikibase\Snaks',
				'Wikibase\ItemDiff',
				'Wikibase\EntityDiff',

				'Wikibase\DataModel\SimpleSiteLink',
			)
		);

	}

}
