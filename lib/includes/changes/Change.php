<?php

namespace Wikibase;
use \ORMRow, \User;

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
class Change extends ORMRow {

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
	 * Constructor.
	 *
	 * @since 1.20
	 *
	 * @param \IORMTable $table
	 * @param array|null $fields
	 * @param boolean $loadDefaults
	 */
	public function __construct( \IORMTable $table, $fields = null, $loadDefaults = false ) {
		if ( is_null( $fields ) ) {
			$fields = array();
		}

		if ( !array_key_exists( 'type', $fields ) ) {
			$fields['type'] = $this->getType();
		}

		parent::__construct( $table, $fields, $loadDefaults );
	}

	/**
	 * Returns the type of change.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'change';
	}

}