<?php

namespace Wikibase;

/**
 * Interface for objects representing changes.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

interface Change {

	/**
	 * Returns the age of the change in seconds.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getAge();

	/**
	 * Returns the type of change.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns the time on which the change was made as a timestamp in TS_MW format.
	 *
	 * @since 0.2
	 *
	 * @return string TS_MW
	 */
	public function getTime();

	/**
	 * Returns the id of the change.
	 *
	 * @since 0.2
	 *
	 * @return int|null
	 */
	public function getId();

	/**
	 * Returns the id of the affected object (ie item or property).
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getObjectId();

}
