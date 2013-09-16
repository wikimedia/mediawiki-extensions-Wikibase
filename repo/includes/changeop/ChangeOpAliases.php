<?php

namespace Wikibase;

use InvalidArgumentException;

/**
 * Class for aliases change operation
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpAliases extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * @since 0.4
	 *
	 * @var string[]
	 */
	protected $aliases;

	/**
	 * @since 0.4
	 *
	 * @var array
	 */
	protected $action;

	/**
	 * @since 0.4
	 *
	 * @param string $language
	 * @param string[] $aliases
	 * @param string $action should be set|add|remove
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, array $aliases, $action ) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		if ( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action needs to be a string' );
		}

		$this->language = $language;
		$this->aliases = $aliases;
		$this->action = $action;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( $this->action === "" || $this->action === "set" ) {
			$this->updateSummary( $summary, 'set', $this->language, $this->aliases );
			$entity->setAliases( $this->language, $this->aliases );
		} elseif ( $this->action === "add" ) {
			$this->updateSummary( $summary, 'add', $this->language, $this->aliases );
			$entity->addAliases( $this->language, $this->aliases );
		} elseif ( $this->action === "remove" ) {
			$this->updateSummary( $summary, 'remove', $this->language, $this->aliases );
			$entity->removeAliases( $this->language, $this->aliases );
		} else {
			throw new ChangeOpException( "Unknown action for change op: $this->action" );
		}
		return true;
	}
}
