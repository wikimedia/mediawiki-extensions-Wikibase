<?php

namespace Wikibase\Database;

/**
 * Object that can create a table in a database given a table definition.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TableBuilder {

	/**
	 * @since 0.1
	 *
	 * @var QueryInterface
	 */
	private $db;

	/**
	 * @since 0.1
	 *
	 * @var MessageReporter|null
	 */
	private $messageReporter;

	/**
	 * @since 0.1
	 *
	 * @param QueryInterface $queryInterface
	 * @param MessageReporter|null $messageReporter
	 */
	public function __construct( QueryInterface $queryInterface, MessageReporter $messageReporter = null ) {
		$this->db = $queryInterface;
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $message
	 */
	private function report( $message ) {
		if ( $this->messageReporter !== null ) {
			$this->messageReporter->reportMessage( $message );
		}
	}

	/**
	 * Creates a table if it does not exist yet.
	 *
	 * @since 0.1
	 *
	 * @param TableDefinition $table
	 */
	public function createTable( TableDefinition $table ) {
		if ( $this->db->tableExists( $table->getName() ) ) {
			$this->report( 'Table "' . $table->getName() . '" exists already, skipping.' );
			return true;
		}

		$this->report( 'Table "' . $table->getName() . '" not found, creating.' );

		$this->db->createTable( $table );
	}

}