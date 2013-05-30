<?php

namespace Wikibase;

use Diff\Comparer\CallbackComparer;
use Diff\Differ;
use Diff\MapPatcher;
use Diff\Patcher;
use MWException;
use Wikibase\Lib\GuidGenerator;

/**
 * Represents a revision of a Wikibase entity.
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
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRevision {

	/**
	 * @since 0.4
	 * @var array
	 */
	protected $entity;

	/**
	 * @var int
	 */
	protected $revision;

	/**
	 * @var string
	 */
	protected $timestamp;

	/**
	 * @param Entity $entity
	 * @param int    $revision
	 * @param string $timestamp
	 */
	public function __construct(
		Entity $entity,
		$revision,
		$timestamp
	) {
		$this->entity = $entity;
		$this->revision = (int)$revision;
		$this->timestamp = wfTimestamp( TS_MW, $timestamp );
	}

	/**
	 * @return Entity
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * @return int
	 */
	public function getRevision() {
		return $this->revision;
	}

	/**
	 * @return string
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}
}
