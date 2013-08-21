<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */

namespace Wikibase\Test\Api;


class EntityTestHelper {

	/**
	 * @var array of currently active handles and their current ids
	 */
	private static $activeHandles = array();
	/**
	 * @var array handles and any registered default output data
	 */
	private static $entityOutput = array();

	/**
	 * @var array of pre defined entity data for use in tests
	 */
	private static $entityData = array(
		'Empty' => array(
			"new" => "item",
			"data" => array(),
		),
		'Berlin' => array(
			"new" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Berlin" ),
					array( "site" => "enwiki", "title" => "Berlin" ),
					array( "site" => "nlwiki", "title" => "Berlin" ),
					array( "site" => "nnwiki", "title" => "Berlin" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Berlin" ),
					array( "language" => "en", "value" => "Berlin" ),
					array( "language" => "no", "value" => "Berlin" ),
					array( "language" => "nn", "value" => "Berlin" ),
				),
				"aliases" => array(
					array( array( "language" => "de", "value" => "Dickes B" ) ),
					array( array( "language" => "en", "value" => "Dickes B" ) ),
					array( array( "language" => "nl", "value" => "Dickes B" ) ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland." ),
					array( "language" => "en", "value" => "Capital city and a federated state of the Federal Republic of Germany." ),
					array( "language" => "no", "value" => "Hovedsted og delstat og i Forbundsrepublikken Tyskland." ),
					array( "language" => "nn", "value" => "Hovudstad og delstat i Forbundsrepublikken Tyskland." ),
				),
			)
		),
		'London' => array(
			"new" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "enwiki", "title" => "London" ),
					array( "site" => "dewiki", "title" => "London" ),
					array( "site" => "nlwiki", "title" => "London" ),
					array( "site" => "nnwiki", "title" => "London" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "London" ),
					array( "language" => "en", "value" => "London" ),
					array( "language" => "no", "value" => "London" ),
					array( "language" => "nn", "value" => "London" ),
				),
				"aliases" => array(
					array(
						array( "language" => "de", "value" => "City of London" ),
						array( "language" => "de", "value" => "Greater London" ),
					),
					array(
						array( "language" => "en", "value" => "City of London" ),
						array( "language" => "en", "value" => "Greater London" ),
					),
					array(
						array( "language" => "nl", "value" => "City of London" ),
						array( "language" => "nl", "value" => "Greater London" ),
					),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Hauptstadt Englands und des Vereinigten Königreiches." ),
					array( "language" => "en", "value" => "Capital city of England and the United Kingdom." ),
					array( "language" => "no", "value" => "Hovedsted i England og Storbritannia." ),
					array( "language" => "nn", "value" => "Hovudstad i England og Storbritannia." ),
				),
			)
		),
		'Oslo' => array(
			"new" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Oslo" ),
					array( "site" => "enwiki", "title" => "Oslo" ),
					array( "site" => "nlwiki", "title" => "Oslo" ),
					array( "site" => "nnwiki", "title" => "Oslo" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Oslo" ),
					array( "language" => "en", "value" => "Oslo" ),
					array( "language" => "no", "value" => "Oslo" ),
					array( "language" => "nn", "value" => "Oslo" ),
				),
				"aliases" => array(
					array(
						array( "language" => "no", "value" => "Christiania" ),
						array( "language" => "no", "value" => "Kristiania" ),
					),
					array(
						array( "language" => "nn", "value" => "Christiania" ),
						array( "language" => "nn", "value" => "Kristiania" ),
					),
					array( "language" => "de", "value" => "Oslo City" ),
					array( "language" => "en", "value" => "Oslo City" ),
					array( "language" => "nl", "value" => "Oslo City" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Hauptstadt der Norwegen." ),
					array( "language" => "en", "value" => "Capital city in Norway." ),
					array( "language" => "no", "value" => "Hovedsted i Norge." ),
					array( "language" => "nn", "value" => "Hovudstad i Noreg." ),
				),
			)
		),
		'Episkopi' => array(
			"new" => "item",
			"data" => array(
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Episkopi Cantonment" ),
					array( "site" => "enwiki", "title" => "Episkopi Cantonment" ),
					array( "site" => "nlwiki", "title" => "Episkopi Cantonment" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Episkopi Cantonment" ),
					array( "language" => "en", "value" => "Episkopi Cantonment" ),
					array( "language" => "nl", "value" => "Episkopi Cantonment" ),
				),
				"aliases" => array(
					array( "language" => "de", "value" => "Episkopi" ),
					array( "language" => "en", "value" => "Episkopi" ),
					array( "language" => "nl", "value" => "Episkopi" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Sitz der Verwaltung der Mittelmeerinsel Zypern." ),
					array( "language" => "en", "value" => "The capital of Akrotiri and Dhekelia." ),
					array( "language" => "nl", "value" => "Het bestuurlijke centrum van Akrotiri en Dhekelia." ),
				),
			)
		),
		'Leipzig' => array(
			"new" => "item",
			"data" => array(
				"labels" => array(
					array( "language" => "de", "value" => "Leipzig" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Stadt in Sachsen." ),
					array( "language" => "en", "value" => "City in Saxony." ),
				),
			)
		),

	);

	/**
	 * @param $handle string of entity to get data for
	 * @return array of entity data
	 * @throws \MWException
	 */
	public static function getEntity( $handle ){
		if( !array_key_exists( $handle, self::$entityData ) ){
			throw new \MWException( "No entity defined with handle {$handle}" );
		}
		$entity = self::$entityData[ $handle ];

		if ( !is_string( $entity['data'] ) ) {
			$entity['data'] = json_encode( $entity['data'] );
		}

		return $entity;
	}

	/**
	 * @param $handle string of entity to get data for
	 * @throws \MWException
	 * @return array|null
	 */
	public static function getEntityClear( $handle ){
		if( !array_key_exists( $handle, self::$activeHandles ) ){
			throw new \MWException( "No entity clear data defined with handle {$handle}" );
		}
		$id = self::$activeHandles[ $handle ];
		self::unRegisterEntity( $handle );
		return array( 'id' => $id, 'data' => '{}', 'clear' => '' );
	}

	public static function getEntityData( $handle ){
		if( !array_key_exists( $handle, self::$entityData ) ){
			throw new \MWException( "No entity defined with handle {$handle}" );
		}
		return self::$entityData[ $handle ]['data'];
	}

	public static function getEntityOutput( $handle ){
		if( !array_key_exists( $handle, self::$entityOutput ) ){
			throw new \MWException( "No entity output defined with handle {$handle}" );
		}
		return self::$entityOutput[ $handle ];
	}

	public static function registerEntity( $handle, $id, $entity = null) {
		self::$activeHandles[ $handle ] = $id;
		if( $entity ){
			self::$entityOutput[ $handle ] = $entity;
		}
	}

	private static function unRegisterEntity( $handle ) {
		if( !array_key_exists( $handle, self::$activeHandles ) ){
			throw new \MWException( "No active entity defined with handle {$handle}" );
		}
		unset( self::$activeHandles[ $handle ] );
	}

	/**
	 * @return array of currently active handles
	 */
	public static function getActiveHandles(){
		$usedHandles = self::$activeHandles;
		return $usedHandles;
	}

	/**
	 * @param $handle string of handles
	 * @throws \MWException
	 * @return null|string id of current handle (if active)
	 */
	public static function getId( $handle ){
		if( !array_key_exists( $handle, self::$activeHandles ) ){
			throw new \MWException( "No entity id defined with handle {$handle}" );
		}
		return self::$activeHandles[ $handle ];
	}

}