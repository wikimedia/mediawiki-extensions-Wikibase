<?php

namespace Wikibase\Api;

use DataValues\IllegalValueException;
use ApiMain;
use Diff\Comparer\CallbackComparer;
use Diff\OrderedListDiffer;
use MWException;
use ApiBase;
use Diff\ListDiffer;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\EntityContent;
use Wikibase\Claim;
use Wikibase\EntityContentFactory;
use Wikibase\ClaimDiffer;
use Wikibase\ClaimSaver;
use Wikibase\ClaimSummaryBuilder;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * API module for creating or updating an entire Claim.
 *
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetClaim extends ApiWikibase {

	/**
	 * @var SnakValidationHelper
	 */
	protected $snakValidation;

	/**
	 * see ApiBase::__construct()
	 *
	 * @param ApiMain $mainModule
	 * @param string  $moduleName
	 * @param string  $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->snakValidation = new SnakValidationHelper(
			$this,
			WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup(),
			WikibaseRepo::getDefaultInstance()->getDataTypeFactory(),
			new ValidatorErrorLocalizer()
		);
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 */
	public function execute() {
		$claim = $this->getClaimFromRequest();

		$this->snakValidation->validateClaimSnaks( $claim );

		$comparer = function( \Comparable $old, \Comparable $new ) {
			return $old->equals( $new );
		};

		$claimDiffer = new ClaimDiffer( new OrderedListDiffer( new CallbackComparer( $comparer ) ) );

		$options = new FormatterOptions( array(
			//TODO: fallback chain
			ValueFormatter::OPT_LANG => $this->getContext()->getLanguage()->getCode()
		) );

		$claimSummaryBuilder = new ClaimSummaryBuilder(
			$this->getModuleName(),
			$claimDiffer,
			WikibaseRepo::getDefaultInstance()->getSnakFormatterFactory()->getSnakFormatter( SnakFormatter::FORMAT_PLAIN, $options )
		);
		$claimSaver = new ClaimSaver();

		$params = $this->extractRequestParams();

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$token = isset( $params['token'] ) ? $params['token'] : '';

		$user = $this->getUser();
		$flags = ( $user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;

		$newRevisionId = null;

		$status = $claimSaver->saveClaim(
			$claim,
			$baseRevisionId,
			$token,
			$user,
			$claimSummaryBuilder,
			$flags
		);
		$this->handleSaveStatus( $status ); // die on error, report warnings, etc

		$statusValue = $status->getValue();
		$newRevisionId = isset( $statusValue['revision'] ) ? $statusValue['revision']->getId() : null;

		if ( $newRevisionId !== null ) {
			$this->getResult()->addValue( null, 'success', 1 );
			$this->getResult()->addValue(
				'pageinfo',
				'lastrevid',
				$newRevisionId
			);
		}

		$this->outputClaim( $claim );
	}

	/**
	 * @since 0.4
	 *
	 * @return Claim
	 */
	protected function getClaimFromRequest() {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\Claim' );

		$params = $this->extractRequestParams();

		try {
			$claim = $unserializer->newFromSerialization( \FormatJson::decode( $params['claim'], true ) );

			assert( $claim instanceof Claim );
			return $claim;
		} catch ( IllegalValueException $illegalValueException ) {
			$this->dieUsage( $illegalValueException->getMessage(), 'invalid-claim' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param Claim $claim
	 */
	protected function outputClaim( Claim $claim ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $claim );
		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'claim',
			$serializer->getSerialized( $claim )
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'claim' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'bot' => false,
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'invalid-claim', 'info' => $this->msg( 'wikibase-api-invalid-claim' )->text() ),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'claim' => 'Claim serialization',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for creating or updating an entire Claim.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetclaim&claim={"id":"Q2$5627445f-43cb-ed6d-3adb-760e85bd17ee","type":"claim","mainsnak":{"snaktype":"value","property":"P1","datavalue":{"value":"City","type":"string"}}}'
			=> 'Set the claim with the given id to property P1 with a string value of "City',
		);
	}

	/**
	 * @see ApiBase::isWriteMode
	 * @return bool true
	 */
	public function isWriteMode() {
		return true;
	}

}
