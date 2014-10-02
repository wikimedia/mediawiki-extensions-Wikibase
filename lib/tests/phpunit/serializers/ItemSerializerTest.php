<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use SiteSQLStore;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\ClaimGuidGenerator;
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
		return 'Wikibase\Lib\Serializers\ItemSerializer';
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
	 * @return array[]
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

	/**
	 * Returns arguments for entity agnostic arguments that can be returned
	 * by validProvider after making sure the provided serialization contains
	 * anything the entity implementing class requires.
	 *
	 * @return array[]
	 */
	private function semiValidProvider() {
		$item = $this->getEntityInstance();

		$validArgs = array();

		$options = new SerializationOptions();
		$options->setOption( EntitySerializer::OPT_PARTS, array( 'aliases' ) );

		$item0 = $item->copy();
		$item0->setAliases( 'en', array( 'foo', 'bar' ) );
		$item0->setAliases( 'de', array( 'baz', 'bah' ) );

		$validArgs[] = array(
			$item0,
			array(
				'id' => $item0->getId()->getSerialization(),
				'type' => $item0->getType(),
				'aliases' => array(
					'en' => array(
						array(
							'value' => 'foo',
							'language' => 'en',
						),
						array(
							'value' => 'bar',
							'language' => 'en',
						),
					),
					'de' => array(
						array(
							'value' => 'baz',
							'language' => 'de',
						),
						array(
							'value' => 'bah',
							'language' => 'de',
						),
					),
				)
			),
			$options
		);

		$options = new SerializationOptions();
		$options->setOption( EntitySerializer::OPT_PARTS, array( 'descriptions', 'labels' ) );

		$item1 = $item->copy();
		$item1->setLabel( 'en', 'foo' );
		$item1->setLabel( 'de', 'bar' );
		$item1->setDescription( 'en', 'baz' );
		$item1->setDescription( 'de', 'bah' );

		$validArgs[] = array(
			$item1,
			array(
				'id' => $item1->getId()->getSerialization(),
				'type' => $item1->getType(),
				'labels' => array(
					'en' => array(
						'value' => 'foo',
						'language' => 'en',
					),
					'de' => array(
						'value' => 'bar',
						'language' => 'de',
					),
				),
				'descriptions' => array(
					'en' => array(
						'value' => 'baz',
						'language' => 'en',
					),
					'de' => array(
						'value' => 'bah',
						'language' => 'de',
					),
				),
			),
			$options
		);

		$item2 = $this->getEntityInstance();

		$options->setOption(
			EntitySerializer::OPT_PARTS,
			array( 'descriptions', 'labels', 'claims', 'aliases' )
		);

		$statement = new Statement(
			new PropertyValueSnak(
				new PropertyId( 'P42' ),
				new StringValue( 'foobar!' )
			)
		);

		$guidGenerator = new ClaimGuidGenerator();
		$statement->setGuid( $guidGenerator->newGuid( $item2->getId() ) );

		$item2->setLabel( 'en', 'foo' );
		$item2->addClaim( $statement );

		$validArgs[] = array(
			$item2,
			array(
				'id' => $item2->getId()->getSerialization(),
				'type' => $item2->getType(),
				'labels' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'foo',
					)
				),
				'claims' => array(
					'P42' => array(
						array(
							'id' => $statement->getGuid(),
							'mainsnak' => array(
								'snaktype' => 'value',
								'property' => 'P42',
								'datavalue' => array(
									'value' => 'foobar!',
									'type' => 'string'
								)
							),
							'type' => 'statement',
							'rank' => 'normal',
						)
					)
				)
			),
			$options
		);

		return $validArgs;
	}

}
