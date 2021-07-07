<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Base class for tests to check for unwanted cross-component usages.
 *
 * The bulk of the work happens in a data provider,
 * so that we can generate one test failure per bad usage,
 * rather than having a single test that loops over the usages
 * and fails once it finds the first one,
 * hiding any later bad usages.
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
abstract class NoBadUsageTest extends TestCase {

	/**
	 * Get the bad patterns along with allowed usages.
	 *
	 * Returns a nested array: on the outer level, the keys are patterns;
	 * on the inner level, the keys are paths, and the values are allowed usages.
	 * Patterns can be regular expressions, surrounded by /slashes/,
	 * or fixed strings (anything not starting with a slash).
	 * Paths can be directories, ending with a slash and covering all files in that directory,
	 * or files (anything not ending with a slash);
	 * all paths are relative to the {#getBaseDir base directory}.
	 * Allowed usages can be integers, specifying the expected number of matches in this file,
	 * or `true` to allow any number of matches.
	 * (For directory paths, only `true` is supported.)
	 */
	abstract protected function getBadPatternsWithAllowedUsages(): array;

	/**
	 * Get the base directory for the test.
	 * All .php files below this directory will be searched and matched
	 * against the {@link #getBadPatternsWithAllowedUsages patterns}.
	 */
	abstract protected function getBaseDir(): string;

	/**
	 * The path to the current test file. Return __FILE__.
	 * (Pattern occurrences in this file are not counted.)
	 */
	abstract protected function getThisFile(): string;

	/**
	 * Split the given pattern => path => allowed mappings
	 * into directory paths (end with a slash) and file paths (all others).
	 *
	 * The directory paths are guaranteed to include all patterns,
	 * even those that have no directory paths (empty array).
	 *
	 * @param array $patterns
	 * @return array( array $dirPaths, array $filePaths )
	 */
	private function splitPatterns( array $patterns ): array {
		$dirPatterns = [];
		$filePatterns = [];

		foreach ( $patterns as $pattern => $paths ) {
			$dirPatterns[$pattern] = [];
			foreach ( $paths as $path => $allowed ) {
				if ( substr( $path, -1 ) === '/' ) {
					$dirPatterns[$pattern][$path] = $allowed;
				} else {
					$filePatterns[$pattern][$path] = $allowed;
				}
			}
		}

		return [ $dirPatterns, $filePatterns ];
	}

	private function removeComments( string $text ): string {
		// remove block comments
		$text = preg_replace( '@/\*.*?\*/@s', '', $text );
		// remove line comments
		$text = preg_replace( '@//.*$@m', '', $text );

		return $text;
	}

	/**
	 * Count usages of the given patterns in the given directory.
	 *
	 * @param array $patterns As returned by {@link #getBadPatternsWithAllowedUsages}.
	 * @param string $baseDir As returned by {@link #getBaseDir}.
	 * @param string $thisFile As returned by {@link #getThisFile}.
	 * @return array( array $usages, array $filePaths ) Two arrays similar to $patterns:
	 * One with the real usages for each file that matched a pattern,
	 * one with the expected usages (i.e. $patterns, but only the non-directory paths).
	 */
	private function countUsages( array $patterns, string $baseDir, string $thisFile ): array {
		[ $dirPatterns, $filePatterns ] = $this->splitPatterns( $patterns );
		$baseDir = realpath( $baseDir );
		$directoryIterator = new RecursiveDirectoryIterator( $baseDir );

		$usages = [];

		/** @var SplFileInfo $fileInfo */
		foreach ( new RecursiveIteratorIterator( $directoryIterator, RecursiveIteratorIterator::SELF_FIRST ) as $fileInfo ) {
			if ( !$fileInfo->isFile() ) {
				continue;
			}
			if ( $fileInfo->getExtension() !== 'php' ) {
				continue;
			}
			$absolutePath = $fileInfo->getRealPath();
			if ( $absolutePath === $thisFile ) {
				continue;
			}
			$relativePath = substr( $absolutePath, strlen( $baseDir ) + 1 ); // + 1 for /

			$text = file_get_contents( $absolutePath );
			$text = $this->removeComments( $text );

			foreach ( $dirPatterns as $pattern => $dirs ) {
				foreach ( $dirs as $dir => $allowed ) {
					if ( strpos( $relativePath, $dir ) === 0 ) {
						$this->assertTrue( $allowed,
							'Only allowed value for directories is `true`' );
						continue 2; // next pattern
					}
				}

				if ( substr( $pattern, 0, 1 ) === '/' ) {
					$matches = preg_match_all( $pattern, $text );
				} else {
					$matches = substr_count( $text, $pattern );
				}

				if ( $matches ) {
					$usages[$pattern][$relativePath] = $matches;
				}
			}
		}

		return [ $usages, $filePatterns ];
	}

	public function provideBadUsages(): iterable {
		[ $usages, $filePatterns ] = $this->countUsages(
			$this->getBadPatternsWithAllowedUsages(),
			$this->getBaseDir(),
			$this->getThisFile()
		);

		foreach ( $usages as $pattern => $files ) {
			foreach ( $files as $file => $count ) {
				$name = "$pattern in $file";
				yield $name => [ $pattern, $file, $count, $filePatterns[$pattern][$file] ?? 0 ];
				unset( $filePatterns[$pattern][$file] );
			}
		}

		// $filePatterns with no matches in $usages
		foreach ( $filePatterns as $pattern => $files ) {
			foreach ( $files as $file => $expectedCount ) {
				$name = "$pattern in $file (unmatched)";
				yield $name => [ $pattern, $file, 0, $expectedCount ];
			}
		}
	}

	/** @dataProvider provideBadUsages */
	public function testNoBadUsages( string $pattern, string $file, int $actualCount, $expectedCount ): void {
		if ( $expectedCount === true ) {
			$this->addToAssertionCount( 1 );
			return;
		}

		$this->assertLessThanOrEqual(
			$expectedCount,
			$actualCount,
			"$file may not use $pattern more often than is allowed"
		);
		$this->assertThat(
			$expectedCount,
			$this->logicalNot( $this->greaterThan( $actualCount ) ),
			'It looks like you successfully reduced the usage of ' .
			"$pattern in $file. Congratulations :) " .
			'Please lower the threshold in the test file accordingly, ' .
			'so that no new usages are accidentally introduced in the future.'
		);
	}

}
