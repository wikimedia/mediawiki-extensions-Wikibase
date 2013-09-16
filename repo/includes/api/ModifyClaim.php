<?php

namespace Wikibase\Api;

use ApiMain;
use ApiBase;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\EntityContent;
use Wikibase\Claim;
use Wikibase\Summary;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Property;
use Wikibase\EntityContentFactory;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Base class for modifying claims.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ModifyClaim extends ApiWikibase {

	/**
	 * @since 0.4
	 *
	 * @var ClaimModificationHelper
	 */
	protected $claimModificationHelper;

	/**
	 * @since 0.5
	 *
	 * @var ClaimGuidParser
	 */
	protected $claimGuidParser;

	/**
	 * @since 0.5
	 *
	 * @var EntityHelper
	 */
	protected $entityHelper;

	/**
	 * see ApiBase::__construct()
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$snakValidation = new SnakValidationHelper(
			$this,
			WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup(),
			WikibaseRepo::getDefaultInstance()->getDataTypeFactory(),
			new ValidatorErrorLocalizer()
		);

		$this->claimModificationHelper = new ClaimModificationHelper(
			$mainModule,
			WikibaseRepo::getDefaultInstance()->getEntityContentFactory(),
			WikibaseRepo::getDefaultInstance()->getSnakConstructionService(),
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getClaimGuidValidator(),
			$snakValidation
		);

		$this->claimGuidParser = WikibaseRepo::getDefaultInstance()->getClaimGuidParser();

		$this->entityHelper = new EntityHelper(
			$mainModule,
			WikibaseRepo::getDefaultInstance()->getEntityIdParser()
		);
	}

	/**
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $content
	 * @param \Wikibase\Summary $summary
	 */
	public function saveChanges( EntityContent $content, Summary $summary ) {
		$status = $this->attemptSaveEntity(
			$content,
			$summary->toString(),
			$this->getFlags()
		);

		$this->addRevisionIdFromStatusToResult( 'pageinfo', 'lastrevid', $status );

		$this->getResult()->addValue(
			null,
			'success',
			1
		);
	}

	/**
	 * @see ApiBase::isWriteMode
	 * @return bool true
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			array(
				array( 'code' => 'failed-save', 'info' => $this->msg( 'wikibase-api-failed-save' )->text() ),
			)
		);
	}

	/**
	 * @see  \Wikibase\Api\ApiWikibase::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( EntityContent $entityContent, array $params ) {
		$permissions = parent::getRequiredPermissions( $entityContent, $params );

		$permissions[] = 'edit';
		return $permissions;
	}

	/**
	 * @since 0.4
	 *
	 * @return integer
	 */
	protected function getFlags() {
		$flags = EDIT_UPDATE;

		$params = $this->extractRequestParams();
		$flags |= ( $this->getUser()->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;

		return $flags;
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			array(
				'summary' => array( ApiBase::PARAM_TYPE => 'string' ),
				'token' => null,
				'baserevid' => array(
					ApiBase::PARAM_TYPE => 'integer',
				),
				'bot' => false,
			)
		);
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'summary' => array(
					'Summary for the edit.',
					"Will be prepended by an automatically generated comment. The length limit of the
					autocomment together with the summary is 260 characters. Be aware that everything above that
					limit will be cut off."
				),
				'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
				'baserevid' => array(
					'The numeric identifier for the revision to base the modification on.',
					"This is used for detecting conflicts during save."
				),
				'bot' => array(
					'Mark this edit as bot',
					'This URL flag will only be respected if the user belongs to the group "bot".'
				),
			)
		);
	}
}
