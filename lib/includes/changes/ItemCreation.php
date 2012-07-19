<?php

namespace Wikibase;

/**
 * Represents the creation of an item.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemCreation extends Change {

	/**
	 * @since 0.1
	 *
	 * @return Item
	 * @throws \MWException
	 */
	public function getItem() {
		$info = $this->getField( 'info' );

		if ( !array_key_exists( 'item', $info ) ) {
			throw new \MWException( 'Cannot get the item when it has not been set yet.' );
		}

		return $info['item'];
	}

	/**
	 * @since 0.1
	 *
	 * @param Item $item
	 */
	public function setItem( Item $item ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['item'] = $item;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param array|null $fields
	 *
	 * @return DiffChange
	 */
	public static function newFromItem( Item $item, array $fields = null ) {
		$instance = new static(
			Changes::singleton(),
			$fields,
			true
		);

		$instance->setItem( $item );

		return $instance;
	}

	/**
	 * Returns whether the change is empty.
	 * If it's empty, it can be ignored.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		if ( $this->hasField( 'info' ) ) {
			return !array_key_exists( 'item', $this->getField( 'info' ) );
		}

		return true;
	}

	/**
	 * @see Change::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'item-add';
	}

}