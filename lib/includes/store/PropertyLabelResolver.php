<?php
 /**
 *
 * Copyright © 24.04.13 by the authors listed below.
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
 * @license GPL 2+
 * @file
 *
 * @author daniel
 */


namespace Wikibase;


/**
 * Resolves property labels (which are unique per language) into entity IDs.
 *
 * @package Wikibase
 */

interface PropertyLabelResolver {

	/**
	 * @param string[] $labels the labels
	 * @param string   $recache Flag, set to 'recache' to discard cached data and fetch fresh data
	 *                 from the database.
	 *
	 * @return EntityId[] a map of strings from $labels to the corresponding entity ID.
	 */
	public function getPropertyIdsForLabels( array $labels, $recache = '' );

}