<?php
/**
 * Interface for DAO objects providing chunked access.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

namespace Wikibase;


/**
 * Interface for DAO objects providing chunked access based on sequential indexes.
 * "holes" in the index sequence are acceptable but should not be frequent.
 *
 * @package Wikibase
 */
interface ChunkAccess {

	/**
	 * Returns a chunk as a list of whatever object is used for data records by
	 * the implementing class.
	 *
	 * @param int $start The first ID in the chunk
	 * @param int $size  The desired size of the chunk
	 *
	 * @return array the desired chunk of rows/objects
	 */
	public function loadChunk( $start, $size );

	/**
	 * Returns the sequential ID of the given data record.
	 *
	 * @param mixed $rec
	 *
	 * @return int
	 */
	public function getRecordId( $rec );

}