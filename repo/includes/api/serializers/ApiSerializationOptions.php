<?php

namespace Wikibase;
use MWException;

/**
 * Options for ApiSerializer objects.
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
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiSerializationOptions {



}

/**
 * Options for Entity serializers.
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
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntitySerializationOptions extends ApiSerializationOptions {

	/**
	 * If keys should be used in the serialization.
	 *
	 * @since 0.2
	 *
	 * @var boolean
	 */
	protected $useKeys;

	/**
	 * The optional properties of the entity that should be included in the serialization.
	 *
	 * @since 0.2
	 *
	 * @var array of string
	 */
	protected $props;

	/**
	 * The language codes of the languages for which internationalized data (ie descriptions) should be returned.
	 * Or null for no restriction.
	 *
	 * @since 0.2
	 *
	 * @var null|array of string
	 */
	protected $languageCodes;

	/**
	 * Sets if keys should be used in the serialization.
	 *
	 * @since 0.2
	 *
	 * @param boolean $useKeys
	 *
	 * @throws MWException
	 */
	public function setUseKeys( $useKeys ) {
		if ( !is_bool( $useKeys ) ) {
			throw new MWException( __METHOD__ . ' expects a boolean' );
		}

		$this->useKeys = $useKeys;
	}

	/**
	 * Returns if keys should be used in the serialization.
	 *
	 * @since 0.2
	 *
	 * @return boolean
	 */
	public function shouldUseKeys() {
		return $this->useKeys;
	}

	/**
	 * Sets the optional properties of the entity that should be included in the serialization.
	 *
	 * @since 0.2
	 *
	 * @param array $props
	 */
	public function setProps( array $props ) {
		$this->props = $props;
	}

	/**
	 * Gets the optional properties of the entity that should be included in the serialization.
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getProps() {
		return $this->props;
	}

	/**
	 * Sets the language codes of the languages for which internationalized data (ie descriptions) should be returned.
	 *
	 * @since 0.2
	 *
	 * @param array|null $languageCodes
	 */
	public function setLanguages( array $languageCodes = null ) {
		$this->languageCodes = $languageCodes;
	}

	/**
	 * Gets the language codes of the languages for which internationalized data (ie descriptions) should be returned.
	 *
	 * @since 0.2
	 *
	 * @return array|null
	 */
	public function getLanguages() {
		return $this->languageCodes;
	}

}