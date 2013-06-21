<?php
/**
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

namespace Wikibase\Api;


use ApiBase;
use DataTypes\DataTypeFactory;
use Status;
use Wikibase\Claim;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Snak;
use Wikibase\Validators\SnakValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * SnakValidationHelper is a component for API modules that performs validation
 * of Snaks and Claims.
 *
 * @package Wikibase\Api
 */
class SnakValidationHelper {

	/**
	 * @var ApiBase
	 */
	protected $apiModule;

	/**
	 * @var SnakValidator
	 */
	protected $snakValidator;

	/**
	 * @var ValidatorErrorLocalizer
	 */
	protected $localizer;

	/**
	 * @param ApiBase                 $apiModule the API module for collaboration
	 * @param PropertyDataTypeLookup  $propertyDataTypeLookup
	 * @param DataTypeFactory         $dataTypeFactory
	 * @param ValidatorErrorLocalizer $localizer
	 *
	 * @todo: instead of taking an ApiBase instance, use an interface that provides dieUsage().
	 */
	public function __construct(
		ApiBase $apiModule,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory,
		ValidatorErrorLocalizer $localizer
	) {
		$this->apiModule = $apiModule;
		$this->snakValidator = new SnakValidator(
			$propertyDataTypeLookup,
			$dataTypeFactory
		);

		$this->localizer = $localizer;
	}

	/**
	 * @see SnakValidator::validate()
	 * @param Snak $snak
	 */
	public function validateSnak( Snak $snak ) {
		$result = $this->snakValidator->validate( $snak );

		if ( !$result->isValid() ) {
			$this->apiDieWithErrors( $result->getErrors() );
		}
	}

	/**
	 * @see SnakValidator::validateClaimSnaks()
	 * @param Claim $claim
	 */
	public function validateClaimSnaks( Claim $claim ) {
		$result = $this->snakValidator->validateClaimSnaks( $claim );


		if ( !$result->isValid() ) {
			$this->apiDieWithErrors( $result->getErrors() );
		}
	}


	/**
	 * Returns a Status representing the given errors.
	 * This can be used for reporting validation failures.
	 *
	 * @param \ValueValidators\Error[] $errors
	 * @return Status
	 */
	public function getValidatorStatus( array $errors ) {
		$status = Status::newGood();

		foreach ( $errors as $error ) {
			$msg = $this->localizer->getErrorMessage( $error );
			$status->fatal( $msg );
		}

		return $status;
	}

	/**
	 * Calls the API module's dieUsage() method with the appropriate
	 * error message derived from the given validator Errors.
	 *
	 * @param \ValueValidators\Error[] $errors
	 */
	public function apiDieWithErrors( array $errors ) {
		$status = $this->getValidatorStatus( $errors );

		if ( $this->apiModule instanceof ApiWikibase ) {
			//TODO: factor this out into a separate helper
			$this->apiModule->handleStatus( $status, 'invalid-snak-value' );
		} else {
			$errorText = $status->getWikiText();
			$this->apiModule->dieUsage( $errorText, 'invalid-snak-value' );
		}
	}
}
