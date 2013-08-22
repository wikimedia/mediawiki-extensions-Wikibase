<?php

namespace Wikibase\Test;

use Wikibase\Item;
use Wikibase\Lib\Serializers\EntitySerializationOptions;

/**
 * @covers Wikibase\Lib\Serializers\ItemSerializer
 *
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemSerializerTest extends EntitySerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ItemSerializer';
	}

	/**
	 * @see EntitySerializerBaseTest::getEntityInstance
	 *
	 * @since 0.2
	 *
	 * @return Item
	 */
	protected function getEntityInstance() {
		$item = Item::newEmpty();
		$item->setId( 42 );
		return $item;
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$validArgs = $this->arrayWrap( $validArgs );

		$item = $this->getEntityInstance();

		$validArgs[] = array(
			$item,
			array(
				'id' => $this->getFormattedIdForEntity( $item ),
				'type' => $item->getType(),
			),
		);

		$options = new EntitySerializationOptions( $this->getIdFormatter() );
		$options->setProps( array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'sitelinks/urls' ) );

		$validArgs[] = array(
			$item,
			array(
				'id' => $this->getFormattedIdForEntity( $item ),
				'type' => $item->getType(),
			),
			$options
		);

		foreach ( $this->semiValidProvider() as $argList ) {
			$validArgs[] = $argList;
		}

		return $validArgs;
	}

}
