<?php

/**
 * Class holding handles for the hooks used by the ValueHandler extension.
 * 
 * @since 0.1
 * 
 * @file
 * @ingroup ValueHandler
 * 
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class ValueHandlerHooks {
	
	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 * 
	 * @since 0.1
	 * 
	 * @param array $files
	 *
	 * @return boolean
	 */
	public static function registerUnitTests( array &$files ) {
		$testFiles = array(
			'valueparser/BoolParser',
			'valueparser/NullParser',
			'valueparser/TitleParser',
			'valueparser/ValueParser',
		);

		foreach ( $testFiles as $file ) {
			$files[] = dirname( __FILE__ ) . '/tests/' . $file . 'Test.php';
		}

		return true;
	}
	
} 
