jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconClose: 'close'
	} ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( '../../../resources/wikibase.wbui2025/lib.js' );
const snakValueVue = require( '../../../resources/wikibase.wbui2025/components/snakValue.vue' );
const indicatorPopover = require( '../../../resources/wikibase.wbui2025/components/indicatorPopover.vue' );
const { mount } = require( '@vue/test-utils' );
const { storeWithServerRenderedHtml } = require( '../piniaHelpers.js' );

describe( 'wikibase.wbui2025.snakValue', () => {
	describe( 'the mounted component', () => {
		const statementId = 'Q1$789eef0c-4108-cdda-1a63-505cdd324564';

		function mountSnakValue( props = {}, snakHashToHtmlMap = {}, propertyToHtmlMap = {}, snakHashWithErrorSet = [] ) {
			return mount( snakValueVue, {
				props,
				global: {
					plugins: [ storeWithServerRenderedHtml( snakHashToHtmlMap, propertyToHtmlMap, snakHashWithErrorSet ) ]
				}
			} );
		}

		it( 'correctly sets the properties in the HTML', async () => {
			const wrapper = await mountSnakValue(
				{
					snak: {
						datatype: 'string',
						hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					},
					statementId
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			expect( snak.text() ).toEqual( 'p1' );
			expect( snak.attributes()[ 'data-snak-hash' ] ).toEqual( 'ee6053a6982690ba0f5227d587394d9111eea401' );
			expect( snak.attributes().class ).toEqual( 'wikibase-wbui2025-snak-value' );
		} );

		it( 'does not include the indicator span when it lacks content', async () => {
			const wrapper = await mountSnakValue(
				{
					snak: {
						datatype: 'string',
						hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					},
					statementId
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);
			await wrapper.vm.$nextTick();
			expect( wrapper.find( { ref: 'snakAnchor' } ).exists() ).toBeFalsy();
		} );

		it.each( [
			[
				'main snak',
				{},
				() => wbui2025.store.setIndicatorHtmlForMainSnak(
					'Q1$789eef0c-4108-cdda-1a63-505cdd324564',
					'<span>Icon1</span>'
				)
			],
			[
				'qualifier',
				{ isQualifier: true },
				() => wbui2025.store.setIndicatorHtmlForQualifier(
					'Q1$789eef0c-4108-cdda-1a63-505cdd324564',
					'ee6053a6982690ba0f5227d587394d9111eea401',
					'<span>Icon1</span>'
				)
			],
			[
				'reference',
				{ referenceHash: '1e638f52eb8d0d3a9453aa05143fa059657dd9d3' },
				() => wbui2025.store.setIndicatorHtmlForReferenceSnak(
					'Q1$789eef0c-4108-cdda-1a63-505cdd324564',
					'1e638f52eb8d0d3a9453aa05143fa059657dd9d3',
					'ee6053a6982690ba0f5227d587394d9111eea401',
					'<span>Icon1</span>'
				)
			]
		] )( 'displays indicators on %s if they are set', async ( _kind, props, storeSetup ) => {
			const wrapper = await mountSnakValue(
				Object.assign( {
					snak: {
						datatype: 'string',
						hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					},
					statementId
				}, props ),
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);
			storeSetup();
			await wrapper.vm.$nextTick();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const indicatorSpan = wrapper.find( { ref: 'snakAnchor' } );
			expect( indicatorSpan.exists() ).toBeTruthy();
			expect( indicatorSpan.text() ).toEqual( 'Icon1' );
		} );

		it( 'sets the musical-notation-value class for musical notation datatype', async () => {
			const wrapper = await mountSnakValue( {
				snak: {
					datatype: 'musical-notation',
					hash: 'musical1234567890123456789012345678901234567890',
					property: 'P1',
					datavalue: { value: '\\relative c\' { c4 e g c }', type: 'string' }
				},
				statementId
			} );

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( '.wikibase-wbui2025-snak-value' );
			const classes = snak.attributes().class.split( ' ' );
			expect( classes ).toContain( 'wikibase-wbui2025-snak-value' );
			expect( classes ).toContain( 'wikibase-wbui2025-musical-notation-value' );
		} );

		it( 'does not set musical-notation-value or math-value class for other datatypes', async () => {
			const wrapper = await mountSnakValue( {
				snak: {
					datatype: 'string',
					hash: 'string1234567890123456789012345678901234567890',
					property: 'P1',
					datavalue: { value: 'test', type: 'string' }
				},
				statementId
			} );

			expect( wrapper.exists() ).toBeTruthy();
			const snak = wrapper.find( '.wikibase-wbui2025-snak-value' );
			const classes = snak.attributes().class.split( ' ' );
			expect( classes ).not.toContain( 'wikibase-wbui2025-musical-notation-value' );
			expect( classes ).not.toContain( 'wikibase-wbui2025-math-value' );
		} );

		it( 'allows indicators to be clicked, and displays popover in that case', async () => {
			const wrapper = await mountSnakValue(
				{
					snak: {
						datatype: 'string',
						hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					},
					statementId
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);
			wbui2025.store.setIndicatorHtmlForMainSnak(
				'Q1$789eef0c-4108-cdda-1a63-505cdd324564',
				'<span>Icon1</span>'
			);
			wbui2025.store.setPopoverContentForMainSnak(
				'Q1$789eef0c-4108-cdda-1a63-505cdd324564',
				[ {
					icon: '<span class="wikibase-wbui2025-icon-edit-small"></span>',
					title: 'Popover Title',
					bodyHtml: '<p>Popover Content</p>'
				} ] );
			await wrapper.vm.$nextTick();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			await snak.find( 'span.indicators' ).trigger( 'click' );
			const popovers = wrapper.findAllComponents( indicatorPopover );
			expect( popovers ).toHaveLength( 1 );
			expect( popovers[ 0 ].props().snakHash ).toEqual( 'ee6053a6982690ba0f5227d587394d9111eea401' );
		} );

		it( 'shows message and adds class if snak HTML has error', async () => {
			const wrapper = await mountSnakValue(
				{
					snak: {
						datatype: 'string',
						hash: 'ee742552ad17e320360d4d17fb60fdd22fe0b6dd',
						property: 'P1',
						datavalue: { value: '\\invalid {', type: 'string' }
					},
					statementId
				},
				{ ee742552ad17e320360d4d17fb60fdd22fe0b6dd:
						'<div class="cdx-message--error mw-ext-score-error cdx-message cdx-message--block"></div>' },
				{},
				[ 'ee742552ad17e320360d4d17fb60fdd22fe0b6dd' ]
			);
			await wrapper.vm.$nextTick();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			expect( snak.find( '.snakValue' ).text() ).toEqual( 'wikibase-undisplayable-value' );
			expect( snak.attributes().class.split( ' ' ) ).toContain( 'wikibase-wbui2025-snak-value--error-message' );
		} );

		it( 'sets the math-value class for mathematical expression datatype', async () => {
			const wrapper = await mountSnakValue( {
				snak: {
					datatype: 'math',
					hash: 'math1234567890123456789012345678901234567890',
					property: 'P1',
					datavalue: { value: 'e=mc^2', type: 'string' }
				}
			} );

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( '.wikibase-wbui2025-snak-value' );
			const classes = snak.attributes().class.split( ' ' );
			expect( classes ).toContain( 'wikibase-wbui2025-snak-value' );
			expect( classes ).toContain( 'wikibase-wbui2025-math-value' );
		} );
	} );

	it( 'defines component', async () => {
		expect( typeof snakValueVue ).toBe( 'object' );
		expect( snakValueVue ).toHaveProperty( 'name', 'WikibaseWbui2025SnakValue' );
	} );

} );
