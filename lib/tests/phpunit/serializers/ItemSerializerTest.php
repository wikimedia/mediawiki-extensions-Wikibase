<?php

namespace Wikibase\Test;

use SiteSQLStore;
use Wikibase\Item;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\ItemSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ItemSerializer
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
		return new $class( new ClaimSerializer( new SnakSerializer() ), SiteSQLStore::newInstance() );
	}

	/**
	 * @see EntitySerializerBaseTest::getEntityInstance
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
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$validArgs = $this->arrayWrap( $validArgs );

		$item = $this->getEntityInstance();

		$validArgs[] = array(
			$item,
			array(
				'id' => $item->getId()->getSerialization(),
				'type' => $item->getType(),
			),
		);

		$options = new SerializationOptions();
		$options->setOption( EntitySerializer::OPT_PARTS, array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'sitelinks/urls' ) );

		$validArgs[] = array(
			$item,
			array(
				'id' => $item->getId()->getSerialization(),
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
