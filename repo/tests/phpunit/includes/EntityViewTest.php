<?php

namespace Wikibase\Test;

use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\EntityView
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
abstract class EntityViewTest extends \MediaWikiLangTestCase {

	/**
	 * @return string
	 */
	protected abstract function getEntityViewClass();

	/**
	 * @param EntityId $id
	 * @param Statement[] $statements
	 *
	 * @return Entity
	 */
	protected abstract function makeEntity( EntityId $id, array $statements = array() );

	/**
	 * Generates a prefixed entity ID based on a numeric ID.
	 *
	 * @param int|string $numericId
	 *
	 * @return EntityId
	 */
	protected abstract function makeEntityId( $numericId );

	protected function makeItem( $id, array $statements = array() ) {
		if ( is_string( $id ) ) {
			$id = new ItemId( $id );
		}

		$item = Item::newEmpty();
		$item->setId( $id );
		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		foreach ( $statements as $statement ) {
			$item->addClaim( $statement );
		}

		return $item;
	}

	protected function makeProperty( $id, $dataTypeId, array $statements = array() ) {
		if ( is_string( $id ) ) {
			$id = new PropertyId( $id );
		}

		$property = Property::newFromType( $dataTypeId );
		$property->setId( $id );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		foreach ( $statements as $statement ) {
			$property->addClaim( $statement );
		}

		return $property;
	}

}
