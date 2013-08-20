<?php

namespace Wikibase;

/**
 * Handles serialization for external changes
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
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChange {

	protected $data;

	protected $changeType;

	protected $entityId;

	protected $entityTitle;

	protected $userName;

	protected $title;

	protected $pageId;

	protected $revId;

	protected $parentId;

	protected $comment;

	public function __construct( array $data, $changeType, $entityId, $entityTitle,
		$userName, $title, $pageId, $revId, $parentId, $comment ) {

		$this->data = $data;
		$this->changeType = $changeType;
		$this->entityId = $entityId;
		$this->entityTitle = $entityTitle;
		$this->userName = $userName;
		$this->title = $title;
		$this->pageId = $pageId;
		$this->revId = $revId;
		$this->parentId = $parentId;
		$this->comment = $comment;
	}

	public function getData() {
		return $this->data;
	}

	public function getChangeType() {
		return $this->changeType;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function getEntityTitle() {
		return $this->entityTitle;
	}

	public function getUserName() {
		return $this->userName;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getPageId() {
		return $this->pageId;
	}

	public function getRevId() {
		return $this->revId;
	}

	public function getParentId() {
		return $this->parentId;
	}

	public function getComment() {
		return $this->comment;
	}

}
