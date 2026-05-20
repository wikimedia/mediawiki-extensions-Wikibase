const { setActivePinia, createPinia } = require( 'pinia' );
const {
	snakValueHtmlForHash,
	snakValueHtmlForHashHasError,
	updateSnakValueHtmlForHash,
	useServerRenderedHtml,
	isDeletedProperty
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

	describe( 'isDeletedProperty', () => {
		it( 'returns false for unknown property', () => {
			expect( isDeletedProperty( 'P1' ) ).toBe( false );
		} );

		it( 'returns true after importFromElement finds data-deleted-property attribute', () => {
			const store = useServerRenderedHtml();
			const element = document.createElement( 'div' );
			element.innerHTML = `<span class="wikibase-wbui2025-property-name-link"
				data-property-id="P1" data-deleted-property="true">
				<a href="#">P1</a></span>`;
			store.importFromElement( element );
			expect( isDeletedProperty( 'P1' ) ).toBe( true );
		} );

		it( 'returns false after importFromElement for property without data-deleted-property', () => {
			const store = useServerRenderedHtml();
			const element = document.createElement( 'div' );
			element.innerHTML = `<span class="wikibase-wbui2025-property-name-link"
				data-property-id="P2"><a href="#">P2</a></span>`;
			store.importFromElement( element );
			expect( isDeletedProperty( 'P2' ) ).toBe( false );
		} );
	} );
} );
