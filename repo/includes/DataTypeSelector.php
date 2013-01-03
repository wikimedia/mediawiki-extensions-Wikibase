<?php

namespace Wikibase;
use DataTypes\DataType;
use MWException;
use Html;

/**
 * DataType selector UI element.
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
 * @ingroup DataTypes
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DataTypeSelector {

	/**
	 * @var DataType[]
	 */
	protected $dataTypes;

	/**
	 * @var string
	 */
	protected $languageCode;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param DataType[] $dataTypes
	 * @param string $languageCode
	 *
	 * @throws MWException
	 */
	public function __construct( array $dataTypes, $languageCode ) {
		if ( !is_string( $languageCode ) ) {
			throw new MWException( '$languageCode should be a string' );
		}

		foreach ( $dataTypes as $dataType ) {
			if ( !( $dataType instanceof DataType ) ) {
				throw new MWException( '$dataTypes should only contain instances of DataTypes\DataType' );
			}
		}

		$this->dataTypes = $dataTypes;
		$this->languageCode = $languageCode;
	}

	/**
	 * Builds and returns the HTML for the DataType selector.
	 *
	 * @since 0.4
	 *
	 * @param string $id
	 * @param string $name
	 *
	 * @return string
	 */
	public function getHtml( $id = 'datatype', $name = 'datatype' ) {
		$dataTypes = array();

		foreach ( $this->dataTypes as $dataType ) {
			$dataTypes[$dataType->getId()] = $dataType->getLabel( $this->languageCode );
		}

		natcasesort( $dataTypes );

		$html = '';

		foreach ( $dataTypes as $typeId => $typeLabel ) {
			$html .= Html::element(
				'option',
				array( 'value' => $typeId ),
				$typeLabel
			);
		}

		$html = Html::rawElement(
			'select',
			array(
				'name' => $name,
				'id' => $id,
				'class' => 'wb-select'
			),
			$html
		);

		return $html;
	}

}