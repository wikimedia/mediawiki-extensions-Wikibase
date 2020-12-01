<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use Exception;
use InvalidArgumentException;
use LogicException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\ChangeOp\ChangeOpsMerge;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class MergeItems extends ApiBase {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var ItemMergeInteractor
	 */
	private $interactor;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @see ApiBase::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param EntityIdParser $idParser
	 * @param ItemMergeInteractor $interactor
	 * @param ApiErrorReporter $errorReporter
	 * @param callable $resultBuilderInstantiator
	 */
	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		EntityIdParser $idParser,
		ItemMergeInteractor $interactor,
		ApiErrorReporter $errorReporter,
		callable $resultBuilderInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->idParser = $idParser;
		$this->interactor = $interactor;

		$this->errorReporter = $errorReporter;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		EntityIdParser $entityIdParser
	): self {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

		return new self(
			$mainModule,
			$moduleName,
			$entityIdParser,
			$wikibaseRepo->newItemMergeInteractor( $mainModule->getContext() ),
			$apiHelperFactory->getErrorReporter( $mainModule ),
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			}
		);
	}

	/**
	 * @param array $parameters
	 * @param string $name
	 *
	 * @return ItemId
	 * @throws ApiUsageException if the given parameter is not a valid ItemId
	 * @throws LogicException
	 */
	private function getItemIdParam( array $parameters, string $name ): ItemId {
		if ( !isset( $parameters[$name] ) ) {
			$this->errorReporter->dieWithError( [ 'param-missing', $name ], 'param-missing' );
		}

		$value = $parameters[$name];

		try {
			return new ItemId( $value );
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieError( $ex->getMessage(), 'invalid-entity-id' );
			throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();

		try {
			$fromId = $this->getItemIdParam( $params, 'fromid' );
			$toId = $this->getItemIdParam( $params, 'toid' );

			$ignoreConflicts = $params['ignoreconflicts'];
			$summary = $params['summary'];

			if ( $ignoreConflicts === null ) {
				$ignoreConflicts = [];
			}

			$this->mergeItems( $fromId, $toId, $ignoreConflicts, $summary, $params['bot'] );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		} catch ( ItemMergeException | RedirectCreationException $ex ) {
			$this->handleException( $ex, $ex->getErrorCode() );
		}
	}

	/**
	 * @param ItemId $fromId
	 * @param ItemId $toId
	 * @param string[] $ignoreConflicts
	 * @param string|null $summary
	 * @param bool $bot
	 * @throws ItemMergeException
	 * @throws RedirectCreationException
	 */
	private function mergeItems( ItemId $fromId, ItemId $toId, array $ignoreConflicts, ?string $summary, bool $bot ): void {
		list( $newRevisionFrom, $newRevisionTo, $redirected )
			= $this->interactor->mergeItems( $fromId, $toId, $ignoreConflicts, $summary, $bot );

		$this->resultBuilder->setValue( null, 'success', 1 );
		$this->resultBuilder->setValue( null, 'redirected', (int)$redirected );

		$this->addEntityToOutput( $newRevisionFrom, 'from' );
		$this->addEntityToOutput( $newRevisionTo, 'to' );
	}

	/**
	 * @param Exception $ex
	 * @param string $errorCode
	 * @param string[] $extraData
	 *
	 * @throws ApiUsageException always
	 */
	private function handleException( Exception $ex, string $errorCode, array $extraData = [] ): void {
		$cause = $ex->getPrevious();

		if ( $cause ) {
			$extraData[] = $ex->getMessage();
			$this->handleException( $cause, $errorCode, $extraData );
		} else {
			$this->errorReporter->dieException( $ex, $errorCode, 0, [ 'extradata' => $extraData ] );
		}
	}

	private function addEntityToOutput( EntityRevision $entityRevision, string $name ): void {
		$entityId = $entityRevision->getEntity()->getId();
		$revisionId = $entityRevision->getRevisionId();

		$this->resultBuilder->addBasicEntityInformation( $entityId, $name );

		$this->resultBuilder->setValue(
			$name,
			'lastrevid',
			(int)$revisionId
		);
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'fromid' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'toid' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'ignoreconflicts' => [
				self::PARAM_ISMULTI => true,
				self::PARAM_TYPE => ChangeOpsMerge::CONFLICT_TYPES,
				self::PARAM_REQUIRED => false,
			],
			'summary' => [
				self::PARAM_TYPE => 'string',
			],
			'bot' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
			],
			'token' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=wbmergeitems&fromid=Q42&toid=Q222' =>
				'apihelp-wbmergeitems-example-1',
			'action=wbmergeitems&fromid=Q555&toid=Q3' =>
				'apihelp-wbmergeitems-example-2',
			'action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=sitelink' =>
				'apihelp-wbmergeitems-example-3',
			'action=wbmergeitems&fromid=Q66&toid=Q99&ignoreconflicts=sitelink|description' =>
				'apihelp-wbmergeitems-example-4',
		];
	}

	/**
	 * @see ApiBase::isWriteMode
	 *
	 * @return bool Always true.
	 */
	public function isWriteMode(): bool {
		return true;
	}

}
