<?php

namespace Wikibase;

/**
 * Class representing a change to an item.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemChange extends Change {

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
	 * Returns whether the change is empty.
	 * If it's empty, it can be ignored.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		if ( $this->hasField( 'info' ) ) {
			$info = $this->getField( 'info' );

			if ( array_key_exists( 'diff', $info ) ) {
				return !$this->getDiff()->hasChanges();
			}
		}

		return true;
	}

	/**
	 * @since 0.1
	 *
	 * @return ItemDiff
	 * @throws \MWException
	 */
	public function getDiff() {
		$info = $this->getField( 'info' );

		if ( !array_key_exists( 'diff', $info ) ) {
			throw new \MWException( 'Cannot get the diff when it has not been set yet.' );
		}

		return $info['diff'];
	}

	/**
	 * @since 0.1
	 *
	 * @param ItemDiff $diff
	 */
	public function setDiff( ItemDiff $diff ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['diff'] = $diff;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param Item $oldItem
	 * @param Item $newItem
	 *
	 * @return ItemChange
	 */
	public static function newFromItems( Item $oldItem, Item $newItem ) {
		$instance = new static(
			Changes::singleton(),
			array(),
			true
		);

		$instance->setItem( $newItem );
		$instance->setDiff( new ItemDiff( $oldItem, $newItem ) );

		return $instance;
	}

}