<?php

namespace Wikibase\Test;

/**
 * @covers Wikibase\Test\DependencyTracker
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DependencyTrackerTest extends \PHPUnit_Framework_TestCase {

	public function provideAddDependenciesFromCode() {
		return array(
			'empty' => array(
				'',
				array(),
				array(),
				array(),
			),
			'class only' => array(
				"class Foo \n{\n}",
				array( 'Foo' ),
				array(),
				array(),
			),
			'class with namespace' => array(
				"namespace Bar;\n" .
				"class Foo \n{\n}",
				array( 'Bar\\Foo' ),
				array(),
				array(),
			),
			'use class' => array(
				"namespace Bar;\n" .
				"use X\\Xyzzy;\n" .
				"class Foo extends Xyzzy {\n}\n",
				array( 'Bar\\Foo' ),
				array( 'X\\Xyzzy' ),
				array(),
			),
			'USE CLASS' => array(
				"NAMESPACE Bar;\n" .
				"USE X\\Xyzzy;\n" .
				"CLASS Foo EXTENDS Xyzzy {\n}\n",
				array( 'Bar\\Foo' ),
				array( 'X\\Xyzzy' ),
				array(),
			),
			'mention class' => array(
				"namespace Bar;\n" .
				"use X\\Xyzzy;\n" .
				"use A\\Azzy;\n" .
				"class Foo implements Azzy {\n".
				"    function foo () {\n".
				"       print 'Some\\Class';\n".
				"    }\n".
				"    function bar () {\n".
				"       print \"Another\\\\Class\";\n".
				"    }\n".
				"}\n",
				array( 'Bar\\Foo' ),
				array( 'X\\Xyzzy', 'A\\Azzy' ),
				array( 'Some\\Class', 'Another\\Class' ),
			),
			'block comment' => array(
				"/*namespace Bar;\n" .
				"use X\\Xyzzy;\n" .
				"class Foo extends Xyzzy2 {\n}*/\n" .
				"namespace Bar2;\n" .
				"use X\\Xyzzy2;\n" .
				"class Foo2 extends Xyzzy2 {\n}\n",
				array( 'Bar2\\Foo2' ),
				array( 'X\\Xyzzy2' ),
				array(),
			),

			//TODO: non-name strings, class-decl in string
			//TODO: usage via implements & extends
			//TODO: usage via new and instanceof
		);
	}

	/**
	 * @dataProvider provideAddDependenciesFromCode
	 */
	public function testAddDependenciesFromCode( $phpCode, $declared, $used, $names ) {
		$tracker = new DependencyTracker();
		$tracker->addDependenciesFromCode( $phpCode );

		$this->assertEquals( $declared, $tracker->getDeclaredClasses(), 'getDeclaredClasses' );
		$this->assertEquals( $used, $tracker->getUsedClasses(), 'getUsedClasses' );
		$this->assertEquals( $names, $tracker->getNameStrings(), 'getNameStrings' );
	}

}
 