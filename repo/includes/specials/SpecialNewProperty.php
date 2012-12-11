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
class SpecialCreateProperty extends SpecialCreateEntity {

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
		parent::__construct( 'CreateProperty' );
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
			// TODO: lookup property by lang+label rather then by id
			try {
				$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );

				$propertyContent->getProperty()->setDataType(
					$libRegistry->getDataTypeFactory()->getType( $this->dataType )
				);
			}
			catch ( MWException $exception ) {
				// TODO: we want a nice internationalized error message
				$status->fatal( $exception->getText() );
			}
		}

		return $status;
	}

	/**
	 * @see SpecialCreateEntity::additionalFormElements()
	 */
	protected function additionalFormElements() {
		return parent::additionalFormElements()
			. Html::element(
				'label',
				array(
					'for' => 'wb-newproperty-datatype',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-newproperty-datatype' )->text()
			)
			. $this->getDataTypes()
			. Html::element( 'br' );
	}

	protected function getDataTypes() {
		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$dataTypeFactory = $libRegistry->getDataTypeFactory();

		$html = '';

		foreach ( \Wikibase\Settings::get( 'dataTypes' ) as $typeId ) {
			$html .= Html::element(
				'option',
				array( 'value' => $typeId ),
				$dataTypeFactory->getType( $typeId )->getLabel( $this->getLanguage()->getCode() )
			);
		}

		return
			\Html::rawElement(
				'select',
				array(
					'name' => 'datatype',
					'id' => 'wb-newproperty-datatype',
					'class' => 'wb-select'
				),
				$html
			);
	}

	/**
	 * @see SpecialCreateEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newproperty-fieldset' );
	}

}
