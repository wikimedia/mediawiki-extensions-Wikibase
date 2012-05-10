<?php

/**
 * Class representing a single change (ie a row in the wb_changes).
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class WikibaseChange extends ORMRow {

	/**
	 * @since 0.1
	 * @var integer
	 */
	protected $itemId;

	/**
	 * Field for caching the linked user.
	 *
	 * @since 0.1
	 * @var User|false
	 */
	protected $user = false;

	/**
	 * Returns the user that made the change.
	 *
	 * @since 0.1
	 *
	 * @return User
	 */
	public function getUser() {
		if ( $this->user === false ) {
			$this->user = User::newFromId( $this->getField( 'user_id' ) );
		}

		return $this->user;
	}

	/**
	 * Returns the age of the change in seconds.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getAge() {
		return time() - (int)wfTimestamp( TS_UNIX, $this->getField( 'time' ) );
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
		return false;
	}

	/**
	 * Overrides the save function to first set the info field to the return value of getInfoBlob.
	 *
	 * @since 0.1
	 *
	 * @param null|string $functionName
	 *
	 * @return boolean Success indicator
	 */
	public function save( $functionName = null ) {
		$this->setField( 'type', get_called_class() );
		return parent::save( $functionName );
	}

}