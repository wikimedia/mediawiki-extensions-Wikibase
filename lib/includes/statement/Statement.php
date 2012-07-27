<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 27.07.12
 * Time: 17:58
 * To change this template use File | Settings | File Templates.
 */
interface Statement {

	/**
	 * @return String
	 */
	public function getPropertyID();

	/**
	 * @return Value
	 */
	public function getValue();

	/**
	 * @param Value $value
	 */
	public function setValue( Value $value );

	/**
	 * @return int
	 */
	public function getRank();

	/**
	 * @param int $rank
	 */
	public function setRank( $rank );

	/**
	 * @return array an array of Snak objects
	 */
	public function listQualifiers();

	/**
	 * @param String $propertyID
	 * @param Value $value
	 */
	public function addQualifier( $propertyID, Value $value );

	/**
	 * @param String $propertyID
	 * @param Value $value
	 */
	public function removeQualifier( $propertyID, Value $value );

	/**
	 * @return array An associative array, mapping ref hashes to arrays of Snak objects.
	 */
	public function listReferences();

	/**
	 * @param string $snaks the Snak objects describing the reference
	 *
	 * @return string the hash of the resulting reference
	 */
	public function addReference( $snaks );

	/**
	 * @param string $refHash the hash of the reference to remove
	 *
	 * @return bool success
	 */
	public function removeReference( $refHash );

	/**
	 * @param string $refHash the hash of the reference to upate
	 * @param array $snaks the Snak objects describing the reference
	 *
	 * @return string the has of the updated reference
	 */
	public function updateReference( $refHash, $snaks );

}
