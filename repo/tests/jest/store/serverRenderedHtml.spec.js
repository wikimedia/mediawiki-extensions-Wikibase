const { setActivePinia, createPinia } = require( 'pinia' );
const {
	snakValueHtmlForHash,
	snakValueHtmlForHashHasError,
	updateSnakValueHtmlForHash,
	useServerRenderedHtml
} = require( '../../../resources/wikibase.wbui2025/store/serverRenderedHtml.js' );

describe( 'Server-rendered HTML Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	it( 'store starts empty', () => {
		const serverRenderedHtml = useServerRenderedHtml();
		expect( serverRenderedHtml.propertyLinks.size ).toBe( 0 );
		expect( serverRenderedHtml.snakValues.size ).toBe( 0 );
	} );

	describe( 'updateSnakValueHtmlForHash', () => {

		it( 'adds regular HTML', () => {
			const hash = 'abcd1234';
			const html = '<p>some HTML mentioning a .cdx-message--error</p>';

			updateSnakValueHtmlForHash( hash, html );

			expect( snakValueHtmlForHash( hash ) ).toBe( html );
			expect( snakValueHtmlForHashHasError( hash ) ).toBe( false );
		} );

		it( 'adds HTML with error', () => {
			const hash = '1234abcd';
			const html = `
<div class="cdx-message--error mw-ext-score-error cdx-message cdx-message--block">
	<span class="cdx-message__icon"></span>
	<div class="cdx-message__content">
		<p>Error!</p>
	</div>
</div>`;

			updateSnakValueHtmlForHash( hash, html );

			expect( snakValueHtmlForHash( hash ) ).toBe( html );
			expect( snakValueHtmlForHashHasError( hash ) ).toBe( true );
		} );

	} );
} );
