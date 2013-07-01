<?php

namespace Wikibase;
use DatabaseBase;
use MessageReporter;

/**
 * Utility class for rebuilding the wb_property_info table.
 *
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoTableBuilder {

	/**
	 * @since 0.4
	 *
	 * @var PropertyInfoTable $table
	 */
	protected $table;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @since 0.4
	 *
	 * @var MessageReporter $reporter
	 */
	protected $reporter;

	/**
	 * Whether all keys should be updated, or only missing keys
	 *
	 * @var bool
	 */
	protected $all = true;

	/**
	 * Whether all keys should be updated, or only missing keys
	 *
	 * @var bool
	 */
	protected $fromId = 1;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 *
	 * @var int
	 */
	protected $batchSize = 100;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param PropertyInfoTable $table
	 */
	public function __construct( PropertyInfoTable $table, EntityLookup $entityLookup ) {
		$this->table = $table;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @return boolean
	 */
	public function getRebuildAll() {
		return $this->all;
	}

	/**
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @return boolean
	 */
	public function getFromId() {
		return $this->fromId;
	}

	/**
	 * @param boolean $all
	 */
	public function setRebuildAll( $all ) {
		$this->all = $all;
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * @param boolean $fromId
	 */
	public function setFromId( $fromId ) {
		$this->fromId = $fromId;
	}

	/**
	 * Sets the reporter to use for reporting progress.
	 *
	 * @param \MessageReporter $reporter
	 */
	public function setReporter( \MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * Rebuild the property info entries.
	 * Use the rebuildPropertyInfo.php maintenance script to invoke this from the command line.
	 *
	 * Database updates a batched into multiple transactions. Do not call this
	 * method within an (explicit) database transaction.
	 *
	 * @since 0.4
	 */
	public function rebuildPropertyInfo() {
		$dbw = $this->table->getWriteConnection();

		$rowId = $this->fromId -1;

		$total = 0;

		$join = array();
		$tables = array( 'wb_entity_per_page' );

		if ( !$this->all ) {
			// Find properties in wb_entity_per_page with no corresponding
			// entry in wb_property_info.

			$piTable = $this->table->getTableName();

			$tables[] = $piTable;
			$join[$piTable] = array( 'LEFT JOIN',
				array(
					'pi_property_id = epp_entity_id',
				)
			);
		}

		while ( true ) {
			// Make sure we are not running too far ahead of the slaves,
			// as that would cause the site to be rendered read only.
			$this->waitForSlaves( $dbw );

			$dbw->begin();

			$props = $dbw->select(
				$tables,
				array(
					'epp_entity_id',
				),
				array(
					'epp_entity_type = ' . $dbw->addQuotes( Property::ENTITY_TYPE ),
					'epp_entity_id > ' . (int) $rowId,
					$this->all ? '1' : 'pi_property_id IS NULL', // if not $all, only add missing entries
				),
				__METHOD__,
				array(
					'LIMIT' => $this->batchSize,
					// XXX: We currently have a unique key defined as `wb_epp_entity` (`epp_entity_id`,`epp_entity_type`).
					//      This SHOULD be the other way around:  `wb_epp_entity` (`epp_entity_type`, `epp_entity_id`).
					//      Once this is fixed, the below should probable be changed to:
					//      'ORDER BY' => 'epp_entity_type ASC, epp_entity_id ASC'
					'ORDER BY' => 'epp_entity_id ASC',
					'FOR UPDATE'
				),
				$join
			);

			$c = 0;

			foreach ( $props as $row ) {
				$id = new EntityId( Property::ENTITY_TYPE, (int)$row->epp_entity_id );
				$this->updatePropertyInfo( $dbw, $id );

				$rowId = $row->epp_entity_id;
				$c+= 1;
			}

			$dbw->commit();

			$this->report( "Updated $c properties, up to ID $rowId." );
			$total += $c;

			if ( $c < $this->batchSize ) {
				// we are done.
				break;
			}
		}

		return $total;
	}

	/**
	 * Wait for slaves (quietly)
	 *
	 * @todo: this should be in the Database class.
	 * @todo: thresholds should be configurable
	 *
	 * @author Tim Starling (stolen from recompressTracked.php)
	 */
	protected function waitForSlaves() {
		$lb = wfGetLB(); //TODO: allow foreign DB, get from $this->table

		while ( true ) {
			list( $host, $maxLag ) = $lb->getMaxLag();
			if ( $maxLag < 2 ) {
				break;
			}

			$this->report( "Slaves are lagged by $maxLag seconds, sleeping..." );
			sleep( 5 );
			$this->report( "Resuming..." );
		}
	}

	/**
	 * Updates the property info entry for the given property.
	 * The property is loaded in full using the EntityLookup
	 * provide to the constructor.
	 *
	 * @see Wikibase\PropertyInfoUpdate
	 *
	 * @since 0.4
	 *
	 * @param \DatabaseBase $dbw the database connection to use
	 * @param EntityId $id the Property to process
	 */
	protected function updatePropertyInfo( \DatabaseBase $dbw, EntityId $id ) {
		if ( $id->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $id );
		}

		$property = $this->entityLookup->getEntity( $id );

		assert( $property instanceof Property );

		$update = new PropertyInfoUpdate( $property, $this->table );
		$update->doUpdate();
	}

	/**
	 * reports a message
	 *
	 * @since 0.4
	 *
	 * @param $msg
	 */
	protected function report( $msg ) {
		if ( $this->reporter ) {
			$this->reporter->reportMessage( $msg );
		}
	}

}