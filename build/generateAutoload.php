<?php

// Temporary script to be used as long as MediaWiki extension classes
// cannot be loaded with PSR-4-compliant autoloading.

namespace Wikibase\Build;

use AutoloadGenerator;
use ExtensionRegistry;
use Maintenance;
use UnexpectedValueException;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * Generates Wikibase autoload info
 */
class GenerateWikibaseAutoload extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Generates Wikibase autoload data' );
	}

	public function execute() {
		$this->generateAutoloadForComponent(
			'lib',
			[ 'includes', 'maintenance' ]
		);

		$this->generateAutoloadForComponent(
			'repo',
			[ 'includes', 'maintenance' ],
			[
				'tests/phpunit/maintenance/MockAddUnits.php',
			]
		);

		$this->generateAutoloadForComponent(
			'client',
			[ 'includes', 'maintenance' ]
		);

		echo "Done.\n\n";
	}

	/**
	 * @param string $componentDir
	 * @param string[] $dirs
	 * @param string[] $files
	 */
	private function generateAutoloadForComponent( $componentDir, array $dirs, array $files = [] ) {
		$base = __DIR__ . '/../' . $componentDir;
		$generator = new AutoloadGenerator( $base );

		$extensionJsonPath = realpath( __DIR__ . '/../extension-' . $componentDir . '-wip.json' );
		if ( $extensionJsonPath !== false ) {
			$extensionJson = file_get_contents( $extensionJsonPath );
			$extensionInfo = json_decode( $extensionJson, true );
			$extensionJsonDir = dirname( $extensionJsonPath );

			$autoloadClasses = [];
			$autoloadNamespaces = [];
			ExtensionRegistry::exportAutoloadClassesAndNamespaces(
				$extensionJsonDir,
				$extensionInfo,
				$autoloadClasses,
				$autoloadNamespaces
			);

			if ( $autoloadClasses !== [] ) {
				throw new UnexpectedValueException( 'We do not use AutoloadClasses in extension.json!' );
			}
			$generator->setPsr4Namespaces( $autoloadNamespaces );
		}

		foreach ( $dirs as $componentDir ) {
			$generator->readDir( $base . '/' . $componentDir );
		}
		foreach ( glob( $base . '/*.php' ) as $file ) {
			$generator->readFile( realpath( $file ) );
		}
		foreach ( $files as $file ) {
			$generator->readFile( realpath( $base . '/' . $file ) );
		}

		$target = $generator->getTargetFileInfo();

		file_put_contents(
			$target['filename'],
			$generator->getAutoload( basename( __DIR__ ) . '/' . basename( __FILE__ ) )
		);
	}

}

$maintClass = GenerateWikibaseAutoload::class;
require_once RUN_MAINTENANCE_IF_MAIN;
