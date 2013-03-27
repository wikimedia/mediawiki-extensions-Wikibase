<?php

namespace Wikibase;
use ValueFormatters\ValueFormatterFactory;
use ValueFormatters\FormatterOptions;

/**
 * ValueFormatter factory with some Wikibase stuff
 *
 * @todo move code, as appropriate, to ValueFormatters and DataTypes, perhaps
 * after some discussion of how we want to handle / structure ValueFormatters
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
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseFormatterFactory {

	/**
	 * @var array
	 */
	protected $dataTypeFormatters;

	/**
	 * @var array
	 */
	protected $valueFormatterMapping;

	/**
	 * @var string
	 */
	protected $langCode;

	/**
	 * @since 0.4
	 *
	 * @param string[] $dataTypeFormatters // $wgValueFormatters
	 * @param string[] $valueFormatterMapping // $wgWBSettings['dataTypeFormatters']
	 * @param string $langCode
	 */
	public function __construct( array $dataTypeFormatters, array $valueFormatterMapping, $langCode ) {
		$this->dataTypeFormatters = $dataTypeFormatters;
		$this->valueFormatterMapping = $valueFormatterMapping;
		$this->langCode = $langCode;
	}

	/**
	 * Get value formatter for a data value type
	 *
	 * @since 0.4
	 *
	 * @param string $dataType
	 * @param mixed[] $extraOptions // additional formatter options
	 *
	 * @throws \MWException
	 *
	 * @return ValueFormatter
	 */
	public function newValueFormatterForDataType( $dataType, array $extraOptions = array() ) {
		wfProfileIn( __METHOD__ );

		$formatterId = $this->getFormatterId( $dataType, $this->dataTypeFormatters );

		if ( $formatterId === null ) {
			wfProfileOut( __METHOD__ );
			throw new \MWException( 'Invalid data type' );
		}

		$options =  $extraOptions !== array() && array_key_exists( $dataType, $extraOptions ) ?
			$extraOptions[$dataType] : array();

		$formatterFactory = new ValueFormatterFactory( $this->valueFormatterMapping );
		$formatterOptions = $this->getFormatterOptions( $dataType, $this->langCode, $options );
		$valueFormatter = $formatterFactory->newFormatter( $formatterId, $formatterOptions );

		wfProfileOut( __METHOD__ );
		return $valueFormatter;
	}

	/**
	 * Get formatter options depending on data type
	 *
	 * @since 0.4
	 *
	 * @param string $dataType
	 * @param string $langCode
	 * @param mixed[] $extraOptions // optional
	 *
	 * return FormatterOptions
	 */
	public function getFormatterOptions( $dataType, $langCode, $extraOptions ) {
		wfProfileIn( __METHOD__ );

		$options = array( 'lang' => $langCode );

		$formatterOptions = new FormatterOptions( array_merge( $options, $extraOptions ) );

		wfProfileOut( __METHOD__ );
		return $formatterOptions;
	}

	/**
	 * Get value formatter id for a data type
	 *
	 * @since 0.4
	 *
	 * @param string $dataType
	 * @param $dataTypeFormatters[]
	 *
	 * @throw new \MWException
	 *
	 * @return string|null
	 */
	public function getFormatterId( $dataType, array $dataTypeFormatters ) {
		wfProfileIn( __METHOD__ );

		if ( !array_key_exists( $dataType, $dataTypeFormatters ) ) {
			throw new \MWException( 'Data type is invalid or not supported.' );
		}

		$formatterId = $dataTypeFormatters[$dataType];

		wfProfileOut( __METHOD__ );
		return $formatterId;
	}
}
