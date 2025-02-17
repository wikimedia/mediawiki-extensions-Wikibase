<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\View;

use LogicException;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\ResourceLoader\CodexModule;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;
use Wikibase\Repo\Hooks\WikibaseRepoHookRunner;

/**
 * @license GPL-2.0-or-later
 */
class ScopedTypeaheadCodexModule extends FileModule {

	private ?CodexModule $codexModule = null;
	private ?HookContainer $hookContainer = null;
	private array $options;
	private array $skinStylesOverride = [];

	/**
	 * @param array $options [optional]
	 *  - codexComponents: array of Codex components to include
	 *  - codexFullLibrary: whether to load the entire Codex library
	 *  - codexStyleOnly: whether to include only style files
	 *  - codexScriptOnly: whether to include only script files
	 * @param string|null $localBasePath [optional]
	 * @param string|null $remoteBasePath [optional]
	 */
	public function __construct( array $options = [], $localBasePath = null, $remoteBasePath = null ) {
		$this->options = $options;
		parent::__construct( $options, $localBasePath, $remoteBasePath );
	}

	private function getCodexModule(): CodexModule {
		if ( $this->hookContainer === null ) {
			throw new LogicException( '`getCodexModule` called before this module is initialized' );
		}
		if ( $this->codexModule === null ) {
			$messagesFromHook = [];
			( new WikibaseRepoHookRunner( $this->hookContainer ) )->onWikibaseRepoSearchableEntityScopesMessages( $messagesFromHook );
			$messages = array_merge(
				array_key_exists( 'messages', $this->options ) ? $this->options['messages'] : [],
				[
					'wikibase-scoped-search-item-scope-name',
					'wikibase-scoped-search-property-scope-name',
				],
				array_values( $messagesFromHook )
			);
			$this->options['messages'] = $messages;
			$this->codexModule = new CodexModule( $this->options, $this->localBasePath, $this->remoteBasePath );
			$this->codexModule->setName( $this->getName() );
			$this->codexModule->setLogger( $this->getLogger() );
			$this->codexModule->setHookContainer( $this->hookContainer );
			$this->codexModule->setConfig( $this->getConfig() );
			$this->codexModule->setSkinStylesOverride( $this->skinStylesOverride );
		}
		return $this->codexModule;
	}

	/** @inheritDoc */
	public function supportsURLLoading() {
		return $this->getCodexModule()->supportsURLLoading();
	}

	/** @inheritDoc */
	public function getMessages() {
		return $this->getCodexModule()->getMessages();
	}

	/** @inheritDoc */
	public function getDefinitionSummary( Context $context ) {
		return $this->getCodexModule()->getDefinitionSummary( $context );
	}

	/** @inheritDoc */
	public function getStyleFiles( Context $context ) {
		return $this->getCodexModule()->getStyleFiles( $context );
	}

	/** @inheritDoc */
	public function getType() {
		return $this->getCodexModule()->getType();
	}

	/** @inheritDoc */
	public function setHookContainer( HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
		parent::setHookContainer( $hookContainer );
	}

	/** @inheritDoc */
	public function getModuleContent( Context $context ) {
		return $this->getCodexModule()->getModuleContent( $context );
	}

	/** @inheritDoc */
	public function setSkinStylesOverride( array $moduleSkinStyles ): void {
		$this->skinStylesOverride = $moduleSkinStyles;
		parent::setSkinStylesOverride( $moduleSkinStyles );
	}

	/** @inheritDoc */
	public function getPackageFiles( Context $context ) {
		return $this->getCodexModule()->getPackageFiles( $context );
	}

}
