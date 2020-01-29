<?php

namespace Wikibase\Lib\Changes;

/**
 * Interface for objects representing changes.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Change {

	/**
	 * @return int Seconds
	 */
	public function getAge();

	/**
	 * Returns the type of change.
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns the time on which the change was made as a timestamp in TS_MW format.
	 *
	 * @return string TS_MW
	 */
	public function getTime();

	/**
	 * Original (repository) user id, or 0 for logged out users.
	 *
	 * @return int
	 */
	public function getUserId();

	/**
	 * @return int|null Number to be used as an identifier when persisting the change.
	 */
	public function getId();

	/**
	 * Returns the id of the affected object (ie item or property).
	 *
	 * @return string
	 */
	public function getObjectId();

}
