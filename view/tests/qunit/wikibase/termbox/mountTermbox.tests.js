/**
 * @license GPL-2.0-or-later
 */
( function ( sinon, QUnit ) {
	const mountTermbox = require(
		'../../../../resources/wikibase/termbox/mountTermbox.js' );
	let testElement;

	QUnit.module( 'wikibase.termbox.mountTermbox', {
		beforeEach: () => {
			testElement = document.createElement( 'div' );
			document.documentElement.appendChild( testElement );
		},
		afterEach: () => {
			testElement.remove();
		}
	} );

	QUnit.test( 'mount the app on an existing wrapper element', function ( assert ) {
		// tree: outer > wrapper > root; outer > unrelated
		const outerElement = document.createElement( 'div' );
		outerElement.classList.add( 'wikibase-entityview-main' );
		testElement.appendChild( outerElement );
		const wrapperElement = document.createElement( 'div' );
		wrapperElement.classList.add( 'wikibase-entitytermsview-wrapper' );
		outerElement.appendChild( wrapperElement );
		const rootElement = document.createElement( 'section' );
		rootElement.classList.add( 'wikibase-entitytermsview' );
		wrapperElement.appendChild( rootElement );
		const unrelatedElement = document.createElement( 'div' );
		unrelatedElement.id = 'toc';
		outerElement.appendChild( unrelatedElement );

		const app = { mount: sinon.spy() };

		mountTermbox( app );

		assert.true( app.mount.calledWith( wrapperElement ) );
		assert.strictEqual( unrelatedElement.parentElement, outerElement );
	} );

	QUnit.test( 'mount the app on a newly created wrapper element', function ( assert ) {
		// tree: outer > root; outer > unrelated
		const outerElement = document.createElement( 'div' );
		outerElement.classList.add( 'wikibase-entityview-main' );
		document.documentElement.appendChild( outerElement );
		const rootElement = document.createElement( 'section' );
		rootElement.classList.add( 'wikibase-entitytermsview' );
		outerElement.appendChild( rootElement );
		const unrelatedElement = document.createElement( 'div' );
		unrelatedElement.id = 'toc';
		outerElement.appendChild( unrelatedElement );

		const app = { mount: sinon.spy() };

		mountTermbox( app );

		assert.true( app.mount.calledOnce );
		// expected tree: outer > wrapper > root; outer > unrelated
		const wrapperElement = app.mount.args[ 0 ][ 0 ];
		assert.strictEqual( wrapperElement.className, 'wikibase-entitytermsview-wrapper' );
		assert.strictEqual( wrapperElement.parentElement, outerElement );
		assert.strictEqual( unrelatedElement.parentElement, outerElement );
		// The root element is discarded, thus has neither a parent nor children
		assert.strictEqual( rootElement.parentElement, null );
		assert.strictEqual( rootElement.childElementCount, 0 );
	} );

	QUnit.test( 'does not mount without root element', function ( assert ) {
		const app = { mount: sinon.spy() };

		mountTermbox( app );

		assert.true( app.mount.notCalled );
	} );

}( sinon, QUnit ) );
