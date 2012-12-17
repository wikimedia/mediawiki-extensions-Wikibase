<?php

use Wikibase\PropertyContent;
use Wikibase\EntityContent;

/**
 * Page for creating new Wikibase properties.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialNewProperty extends SpecialCreateEntity {

	/**
	 * @since 0.2
	 * 
	 * @var string|null
	 */
	protected $dataType = null;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'NewProperty' );
	}

	/**
	 * @see SpecialCreateEntity::prepareArguments()
	 */
	protected function prepareArguments() {
		parent::prepareArguments();
		$this->dataType = $this->getRequest()->getVal( 'datatype', isset( $this->parts[2] ) ? $this->parts[2] : '' );
		return true;
	}

	/**
	 * @see SpecialCreateEntity::hasSufficientArguments()
	 */
	protected function hasSufficientArguments() {
		// TODO: Needs refinement
		return parent::hasSufficientArguments() && ( $this->dataType !== '' );
	}

	/**
	 * @see SpecialCreateEntity::createEntityContent
	 */
	protected function createEntityContent() {
		return \Wikibase\PropertyContent::newEmpty();
	}

	/**
	 * @see SpecialCreateEntity::modifyEntity()
	 *
	 * @param EntityContent $propertyContent
	 *
	 * @return Status
	 */
	protected function modifyEntity( EntityContent &$propertyContent ) {
		/**
		 * @var PropertyContent $propertyContent
		 */
		$status = parent::modifyEntity( $propertyContent );

		if ( $this->dataType !== '' ) {
			$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );

			$dataType = $libRegistry->getDataTypeFactory()->getType( $this->dataType );

			if ( $dataType === null ) {
				$status->fatal( 'wikibase-newproperty-invalid-datatype' );
			}
			else {
				$propertyContent->getProperty()->setDataType( $dataType );
			}
		}

		return $status;
	}

	/**
	 * @see SpecialCreateEntity::additionalFormElements()
	 */
	protected function additionalFormElements() {
		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$dataTypeFactory = $libRegistry->getDataTypeFactory();

		$selector = new \Wikibase\DataTypeSelector( $dataTypeFactory->getTypes(), $this->getLanguage()->getCode() );

		return parent::additionalFormElements()
			. Html::element(
				'label',
				array(
					'for' => 'wb-newproperty-datatype',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-newproperty-datatype' )->text()
			)
			. $selector->getHtml( 'wb-newproperty-datatype' )
			. Html::element( 'br' );
	}

	/**
	 * @see SpecialCreateEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newproperty-fieldset' );
	}

}
