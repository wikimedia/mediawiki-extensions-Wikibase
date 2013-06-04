<?php

namespace Wikibase;

use InvalidArgumentException;
use MWExceptiontion;

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
class ChangeOpAliases implements ChangeOp {

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
	 * @param string $action
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
	 * Applies the change to the given entity
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @throws MWException
	 */
	public function apply( Entity $entity ) {
		if ( $this->action === "" || $this->action === "set" ) {
			$entity->setAliases( $this->language, $this->aliases );
		} elseif ( $this->action === "add" ) {
			$entity->addAliases( $this->language, $this->aliases );
		} elseif ( $this->action === "remove" ) {
			$entity->removeAliases( $this->language, $this->aliases );
		} else {
			throw new \MWException( "Unknown action: $this->action" );
		}
	}

}
