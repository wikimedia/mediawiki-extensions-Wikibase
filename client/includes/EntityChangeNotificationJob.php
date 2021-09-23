<?php

declare( strict_types = 1 );

namespace Wikibase\Client;

use Job;
use Psr\Log\LoggerInterface;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\ItemChange;

/**
 * @license GPL-2.0-or-later
 */
class EntityChangeNotificationJob extends Job {

	/**
	 * @var EntityChange[]
	 */
	private $changes;

	/**
	 * @var ChangeHandler
	 */
	private $changeHandler;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		ChangeHandler $changeHandler,
		EntityIdParser $entityIdParser,
		LoggerInterface $logger,
		$params
	) {
		parent::__construct( 'EntityChangeNotification', $params );

		$this->changeHandler = $changeHandler;
		$this->entityIdParser = $entityIdParser;
		$this->logger = $logger;
		$this->changes = array_map( [ $this, 'reconstructChangeFromFields' ], $params['changes'] );
	}

	public static function newFromGlobalState( $unused, array $params ): self {
		return new self(
			WikibaseClient::getChangeHandler(),
			WikibaseClient::getEntityIdParser(),
			WikibaseClient::getLogger(),
			$params
		);
	}

	/**
	 * @inheritDoc
	 */
	public function run(): bool {
		if ( empty( $this->changes ) ) {
			$this->logger->error( __METHOD__ . ': Job without changes, which should never have been scheduled.' );
			return true;
		}
		$this->logger->info( __METHOD__ . ': handling {numberOfChanges} change(s) for {entity}', [
			'entity' => $this->changes[0]->getEntityId()->getSerialization(),
			'numberOfChanges' => count( $this->changes ),
		] );
		$this->changeHandler->handleChanges( $this->changes, $this->getRootJobParams() );

		return true;
	}

	private function reconstructChangeFromFields( array $changeFields ): EntityChange {
		$entityId = $this->entityIdParser->parse( $changeFields[ChangeRow::OBJECT_ID] );
		if ( explode( '~', $changeFields[ChangeRow::TYPE] )[0] === 'wikibase-item' ) {
			$entityChange = new ItemChange( $changeFields );
		} else {
			$entityChange = new EntityChange( $changeFields );
		}
		$entityChange->setEntityId( $entityId );
		return $entityChange;
	}
}
