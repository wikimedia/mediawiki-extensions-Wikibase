<?php

namespace Wikibase\Test;

use Wikibase\Item;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\ItemSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ItemSerializer
 *
 * @since 0.2
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
	 * @return ItemSerializer
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class( new ClaimSerializer( new SnakSerializer() ) );
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

		$options = new SerializationOptions();
		$options->setOption( EntitySerializer::OPT_PARTS, array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'sitelinks/urls' ) );

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
