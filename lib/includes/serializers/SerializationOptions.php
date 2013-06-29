<?php

namespace Wikibase\Lib\Serializers;
use Language;
use MWException;
use ValueFormatters\ValueFormatter;
use Wikibase\EntityId;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\LanguageFallbackChain;

/**
 * Options for Serializer objects.
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
 * TODO: use PDO like options system as done in ValueParsers
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializationOptions {

	/**
	 * @since 0.3
	 * @var boolean
	 */
	protected $indexTags = false;

	/**
	 * Sets if tags should be indexed.
	 * The MediaWiki API needs this when building API results in formats such as XML.
	 *
	 * @since 0.3
	 *
	 * @param boolean $indexTags
	 *
	 * @throws MWException
	 */
	public function setIndexTags( $indexTags ) {
		if ( !is_bool( $indexTags ) ) {
			throw new MWException( 'Expected boolean, got something else' );
		}

		$this->indexTags = $indexTags;
	}

	/**
	 * Returns if tags should be indexed.
	 *
	 * @since 0.3
	 *
	 * @return boolean
	 */
	public function shouldIndexTags() {
		return $this->indexTags;
	}

	/**
	 * Sets if keys should be used in the serialization.
	 *
	 * @since 0.2
	 * @deprecated
	 *
	 * @param boolean $useKeys
	 *
	 * @throws MWException
	 */
	public function setUseKeys( $useKeys ) {
		if ( !is_bool( $useKeys ) ) {
			throw new MWException( __METHOD__ . ' expects a boolean' );
		}

		$this->indexTags = !$useKeys;
	}

	/**
	 * Returns if keys should be used in the serialization.
	 *
	 * @since 0.2
	 * @deprecated
	 *
	 * @return boolean
	 */
	public function shouldUseKeys() {
		return !$this->indexTags;
	}
}

/**
 * Options for MultiLang Serializers.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jeroen De Dauw < tobias.gritschacher@wikimedia.de >
 */
class MultiLangSerializationOptions extends SerializationOptions {
	/**
	 * The language codes of the languages for which internationalized data (ie descriptions) should be returned.
	 * Or null for no restriction.
	 *
	 * @since 0.2
	 *
	 * @var null|array of string
	 */
	protected $languageCodes = null;

	/**
	 * The language fallback chains of languages defined in $languageCodes. When $languageCodes is null, this is null
	 * too currently, but don't depend on anything in this variable for forward compatibility reasons in this case.
	 *
	 * @since 0.4
	 *
	 * @var null|array of LanguageFallbackChain
	 */
	protected $languageFallbackChains = null;

	/**
	 * Sets the language codes or language fallback chains of the languages for which internationalized data
	 * (ie descriptions) should be returned.
	 *
	 * @since 0.2
	 *
	 * @param array|null $languages array of strings (back compat, as language codes)
	 *                     or LanguageFallbackChain objects (requested language codes as keys, to identify chains)
	 */
	public function setLanguages( array $languages = null ) {
		if ( $languages === null ) {
			$this->languageCodes = null;
			$this->languageFallbackChains = null;

			return;
		}

		$this->languageCodes = array();
		$this->languageFallbackChains = array();

		foreach ( $languages as $languageCode => $languageFallbackChain ) {
			if ( is_numeric( $languageCode ) ) {
				$languageCode = $languageFallbackChain;
				$languageFallbackChain = LanguageFallbackChain::newFromLanguage(
					Language::factory( $languageCode ), LanguageFallbackChain::FALLBACK_SELF
				);
			}

			$this->languageCodes[] = $languageCode;
			$this->languageFallbackChains[] = $languageFallbackChain;
		}
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

	/**
	 * Gets an associative array with language codes as keys and their fallback chains as values, or null.
	 *
	 * @since 0.4
	 *
	 * @return array|null
	 */
	public function getLanguageFallbackChains() {
		if ( $this->languageCodes === null ) {
			return null;
		} else {
			return array_combine( $this->languageCodes, $this->languageFallbackChains );
		}
	}
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntitySerializationOptions extends MultiLangSerializationOptions {

	const SORT_ASC = 'ascending';
	const SORT_DESC = 'descending';
	const SORT_NONE = 'none';

	/**
	 * The optional properties of the entity that should be included in the serialization.
	 *
	 * @since 0.2
	 *
	 * @var array of string
	 */
	protected $props = array(
		'aliases',
		'descriptions',
		'labels',
		'claims',
		// TODO: the following properties are not part of all entities, listing them here is not nice
		'datatype', // property specific
		'sitelinks', // item specific
	);

	/**
	 * Names of fields to sort on.
	 *
	 * @since 0.2
	 *
	 * @var array
	 */
	protected $sortFields = array();

	/**
	 * The direction the result should be sorted in.
	 *
	 * @since 0.2
	 *
	 * @var string Element of the EntitySerializationOptions::SORT_ enum
	 */
	protected $sortDirection = self::SORT_NONE;

	/**
	 * @since 0.4
	 *
	 * @var ValueFormatter
	 */
	protected $idFormatter;

	/**
	 * @since 0.4
	 *
	 * @param ValueFormatter $formatter
	 */
	public function __construct( ValueFormatter $formatter ) {
		$this->idFormatter = $formatter;
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
	 * Adds a prop to the list of optionally included elements of the entity.
	 *
	 * @since 0.3
	 *
	 * @param string $name
	 */
	public function addProp( $name ) {
		$this->props[] = $name;
	}

	/**
	 * Removes a prop from the list of optionally included elements of the entity.
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 */
	public function removeProp ( $name ) {
		$this->props = array_diff( $this->props, array( $name ) );
	}

	/**
	 * Sets the names of fields to sort on.
	 *
	 * @since 0.2
	 *
	 * @param array $sortFields
	 */
	public function setSortFields( array $sortFields ) {
		$this->sortFields = $sortFields;
	}

	/**
	 * Returns the names of fields to sort on.
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getSortFields() {
		return $this->sortFields;
	}

	/**
	 * Sets the direction the result should be sorted in.
	 *
	 * @since 0.2
	 *
	 * @param string $sortDirection Element of the EntitySerializationOptions::SORT_ enum
	 * @throws MWException
	 */
	public function setSortDirection( $sortDirection ) {
		if ( !in_array( $sortDirection, array( self::SORT_ASC, self::SORT_DESC, self::SORT_NONE ) ) ) {
			throw new MWException( 'Invalid sort direction provided' );
		}

		$this->sortDirection = $sortDirection;
	}

	/**
	 * Returns the direction the result should be sorted in.
	 *
	 * @since 0.2
	 *
	 * @return string Element of the EntitySerializationOptions::SORT_ enum
	 */
	public function getSortDirection() {
		return $this->sortDirection;
	}

	/**
	 * @since 0.4
	 *
	 * @return ValueFormatter
	 */
	public function getIdFormatter() {
		return $this->idFormatter;
	}

}
