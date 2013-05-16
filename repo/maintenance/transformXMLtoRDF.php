<?php
/**
 * Transform an XML dump into an RDF dump.
 * Based on mediawiki/maintenance/importDump.php
 *
 * Use as follows:
 *
 * php createRDFdump.php pages-meta-current.xml.bz2 --output export.nt.bz2 --types types.txt
 *
 * Copyright © 2005 Brion Vibber <brion@pobox.com>
 * http://www.mediawiki.org/
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandecic < vrandecic@gmail.com >
 * @author Brion Vibber <brion@pobox.com>
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that transforms the XML dump into an RDF dump.
 *
 * @ingroup WikibaseRepo
 */
class transformXMLtoRDF extends Maintenance {
	private $reportingInterval = 1000;
	private $pageCount = 0;
	private $revCount = 0;
	private $rdfSerializer;
	private $outputFile;

	function __construct() {
		parent::__construct();
		$gz = in_array( 'compress.zlib', stream_get_wrappers() ) ? 'ok' : '(disabled; requires PHP zlib module)';
		$bz2 = in_array( 'compress.bzip2', stream_get_wrappers() ) ? 'ok' : '(disabled; requires PHP bzip2 module)';

		$this->mDescription = <<<TEXT
This script reads pages from an XML file as produced from Special:Export or
dumpBackup.php, and transforms them into an RDF/NTriples file containing the knowledge base.

Compressed XML files may be read directly:
  .gz $gz
  .bz2 $bz2
  .7z (if 7za executable is in PATH)
TEXT;
		$this->stderr = fopen( "php://stderr", "wt" );
		$this->addOption( 'report',
			'Report position and speed after every n entities processed', false, true );
		$this->addOption( 'types',
			'file containing lines each with a space-separated tuple of property ID and type',
			true, true );
		$this->addOption( 'output', 'name of the output file (bzipped NTriples, defaults to export.nt.bz2)', false, true );
		$this->addOption( 'debug', 'Output extra verbose debug information' );
		$this->addOption( 'baseuri', 'namespace to export the local URIs to', false, true);
		$this->addArg( 'file', 'Dump file to transform [else use stdin]', false );
	}

	public function execute() {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$this->reportingInterval = intval( $this->getOption( 'report', 1000 ) );
		if ( !$this->reportingInterval ) {
			$this->reportingInterval = 1000; // avoid division by zero
		}
		$outputFileName = strval( $this->getOption( 'output' ) );
		if ( !$outputFileName ) {
			$outputFileName = 'export.rdf.bz2'; // default
		}
		$baseURI = strval( $this->getOption( 'baseuri' ) );
		if ( !$baseURI ) {
			$baseURI = $repo->getRdfBaseURI();
		}

		$this->outputFile = bzopen( $outputFileName, 'w' );

		$rdfFormat = \Wikibase\RdfSerializer::getFormat( 'nt' ); // nt can be just concatenated, which is used here

		$idFormatter =

		$this->rdfSerializer = new \Wikibase\RdfSerializer(
			$rdfFormat,
			$baseURI,
			null, // no entity look up needed, because we transform the whole thing anyway
			null, // TODO: here, a datatype look up might be needed as soon as we start exporting claims
			// this would be based on the types file which is supported as a parameter
			$repo->getIdFormatter()
		);

		if ( $this->hasArg() ) {
			$this->importFromFile( $this->getArg() );
		} else {
			$this->importFromStdin();
		}

		bzclose( $this->outputFile );

		$this->output( "Done!\n" );
	}

	public function reportPage( $page ) {
		$this->pageCount++;
	}

	/**
	 * @param $rev Revision
	 * @return mixed
	 */
	public function handleRevision( WikiRevision $rev ) {
		$title = $rev->getTitle();
		if ( !$title ) {
			$this->progress( "Got bogus revision with null title!" );
			return;
		}
		$isItem = ( $rev->getModel() === CONTENT_MODEL_WIKIBASE_ITEM );
		$isProperty = ( $rev->getModel() === CONTENT_MODEL_WIKIBASE_PROPERTY );
		$isEntity = $isItem OR $isProperty;

		if ( $isEntity ) {
			$entity = $rev->getContent()->getEntity();
			$data = $this->rdfSerializer->serializeEntity( $entity, $rev );
			bzwrite( $this->outputFile, $data );
		}

		$this->revCount++;
		$this->report();
	}

	public function report( $final = false ) {
		if ( $final xor ( $this->pageCount % $this->reportingInterval == 0 ) ) {
			$this->showReport();
		}
	}

	private function showReport() {
		if ( !$this->mQuiet ) {
			$delta = microtime( true ) - $this->startTime;
			if ( $delta ) {
				$rate = sprintf( "%.2f", $this->pageCount / $delta );
				$revrate = sprintf( "%.2f", $this->revCount / $delta );
			} else {
				$rate = '-';
				$revrate = '-';
			}
			# Logs dumps don't have page tallies
			if ( $this->pageCount ) {
				$this->progress( "$this->pageCount ($rate pages/sec $revrate revs/sec)" );
			} else {
				$this->progress( "$this->revCount ($revrate revs/sec)" );
			}
		}
	}

	private function progress( $string ) {
		fwrite( $this->stderr, $string . "\n" );
	}

	private function importFromFile( $filename ) {
		if ( preg_match( '/\.gz$/', $filename ) ) {
			$filename = 'compress.zlib://' . $filename;
		} elseif ( preg_match( '/\.bz2$/', $filename ) ) {
			$filename = 'compress.bzip2://' . $filename;
		} elseif ( preg_match( '/\.7z$/', $filename ) ) {
			$filename = 'mediawiki.compress.7z://' . $filename;
		}

		$file = fopen( $filename, 'rt' );
		return $this->importFromHandle( $file );
	}

	private function importFromStdin() {
		$file = fopen( 'php://stdin', 'rt' );
		if ( self::posix_isatty( $file ) ) {
			$this->maybeHelp( true );
		}
		return $this->importFromHandle( $file );
	}

	private function importFromHandle( $handle ) {
		$this->startTime = microtime( true );

		$source = new ImportStreamSource( $handle );
		$importer = new WikiImporter( $source );

		if ( $this->hasOption( 'debug' ) ) {
			$importer->setDebug( true );
		}
		$importer->setNoUpdates( true );

		$importer->setPageCallback( array( &$this, 'reportPage' ) );
		$this->importCallback = $importer->setRevisionCallback( array( &$this, 'handleRevision' ) );
		$this->uploadCallback = $importer->setUploadCallback( null );
		$this->logItemCallback = $importer->setLogItemCallback( null );
		$importer->setPageOutCallback( null );

		return $importer->doImport();
	}
}

$maintClass = 'transformXMLtoRDF';
require_once RUN_MAINTENANCE_IF_MAIN;
