<?php

namespace Wikibase;
use ApiResult, MWException;

interface ApiSerializer {

	/**
	 * Serializes the provided object to API output and returns this serialization.
	 *
	 * @since 0.2
	 *
	 * @param ApiResult $apiResult
	 * @param mixed $object
	 *
	 * @return array
	 */
	public function getSerialized( ApiResult $apiResult, $object );

	// TODO: options interface and set options method

}

class StatementsSerializer implements ApiSerializer {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param ApiResult $apiResult
	 * @param mixed $object
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( ApiResult $apiResult, $object ) {
		if ( !( $object instanceof Statements ) ) {
			throw new MWException( 'StatementsSerializer can only serialize Statements objects' );
		}

		$serialization = array();

		$props = array(); // TODO

		$statementSerializer = new StatementSerializer();

		foreach ( $props as $prop ) {
			$statements = array(); // TODO

			foreach ( $statements as &$statement ) {
				$statement = $statementSerializer->getSerialized( $apiResult, $statement );
			}

			$apiResult->setIndexedTagName( $statements, 'statement' );

			$serialization[42 /* TODO propid */] = $statements;
		}

		$apiResult->setIndexedTagName( $serialization, 'property' );

		return $serialization;
	}

}

class StatementSerializer implements ApiSerializer {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param ApiResult $apiResult
	 * @param mixed $statement
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( ApiResult $apiResult, $statement ) {
		if ( !( $statement instanceof Statement ) ) {
			throw new MWException( 'StatementSerializer can only serialize Statement objects' );
		}

		$serialization = array();

		$mainSnak = $statement->getClaim()->getMainSnak();

		$entityFactory = EntityFactory::singleton();

		$serialization['property'] = $entityFactory->getPrefixedId( Property::ENTITY_TYPE, $mainSnak->getPropertyId() );

		$snakSerializer = new SnakSerializer();
		$serialization['value'] = $snakSerializer->getSerialized( $apiResult, $mainSnak );

		// TODO

		return $serialization;
	}

}

class SnakSerializer implements ApiSerializer {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param ApiResult $apiResult
	 * @param mixed $snak
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( ApiResult $apiResult, $snak ) {
		if ( !( $snak instanceof Snak ) ) {
			throw new MWException( 'SnakSerializer can only serialize Snak objects' );
		}

		$serialization = array();

		$serialization['snaktype'] = $snak->getType();

		$entityFactory = EntityFactory::singleton();

		if ( in_array( $snak->getType(), array( 'instance', 'subclass' ) ) ) {
			$serialization['item'] = $entityFactory->getPrefixedId( Item::ENTITY_TYPE, $snak->getItemId() );
		}
		else {
			$serialization['property'] = $entityFactory->getPrefixedId( Property::ENTITY_TYPE, $snak->getPropertyId() );
		}

		if ( $snak->getType() === 'value' ) {
			$serialization['value'] = $snak->getDataValue();
		}

		return $serialization;
	}

}
