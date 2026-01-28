jest.mock(
	'../../../resources/wikibase.wbui2025/repoSettings.json',
	() => ( {
		viewUiTags: []
	} ),
	{ virtual: true }
);

const { setActivePinia, createPinia } = require( 'pinia' );
const {
	getIndicatorsHtmlForSnakHash,
	getPopoverContentForSnakHash,
	setIndicatorsHtmlForSnakHash,
	setPopoverContentForSnakHash,
	useSavedStatementsStore
} = require( '../../../resources/wikibase.wbui2025/store/savedStatementsStore.js' );
const { updateSnakValueHtmlForHash } = require( '../../../resources/wikibase.wbui2025/store/serverRenderedHtml.js' );
const { api } = require( '../../../resources/wikibase.wbui2025/api/api.js' );

const testSnakHash = '1a97f9d234d412c3daae7fc5e2a6a8ade8742638';
const testMainSnakHash = 'e08e4506d2e3f370a5e8ab79647df309';
const testStatementId = 'Q3$e4596467-53fe-45c2-91d4-a6b696b82a46';
const testStatement = {
	id: testStatementId,
	mainsnak: {
		snaktype: 'value',
		hash: testMainSnakHash,
		datavalue: {
			value: 'test value',
			type: 'string'
		}
	},
	rank: 'normal',
	'qualifiers-order': [ 'P1' ],
	qualifiers: {
		P1: [ {
			snaktype: 'value',
			property: 'P1',
			hash: '1a97f9d234d412c3daae7fc5e2a6a8ade8742638',
			datavalue: {
				value: "I'm its qualifier",
				type: 'string'
			},
			datatype: 'string'
		} ]
	},
	references: [ {
		hash: '32c451f202d636407a08953a1754752a000909da',
		snaks: {
			P1: [ {
				snaktype: 'value',
				property: 'P1',
				hash: '8374f86cf4335926633fe80c2adbad3b2865e075',
				datavalue: {
					value: "Ofc it's a string reference",
					type: 'string'
				},
				datatype: 'string'
			} ],
			P2: [ {
				snaktype: 'value',
				property: 'P2',
				hash: '4fd80c9f4a37746f632dbe390417a927f6518668',
				datavalue: {
					value: {
						time: '+1999-00-00T00:00:00Z',
						timezone: 0,
						before: 0,
						after: 0,
						precision: 9,
						calendarmodel: 'http://www.wikidata.org/entity/Q1985727'
					},
					type: 'time'
				},
				datatype: 'time'
			} ]
		},
		'snaks-order': [ 'P1', 'P2' ]
	} ]
};

describe( 'Statements Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	it( 'store starts empty', () => {
		const savedStatementsStore = useSavedStatementsStore();
		expect( savedStatementsStore.statements.size ).toBe( 0 );
		expect( savedStatementsStore.properties.size ).toBe( 0 );
	} );

	it( 'can be populated with claims', () => {
		const savedStatementsStore = useSavedStatementsStore();
		const testClaims = {
			P5: [ testStatement ]
		};
		savedStatementsStore.populateWithClaims( testClaims );
		expect( savedStatementsStore.statements.size ).toBe( 1 );
		expect( savedStatementsStore.properties.size ).toBe( 1 );
	} );

	it( 'looks up html for snaks and properties with no stored html', async () => {
		const savedStatementsStore = useSavedStatementsStore();
		const testClaims = {
			P5: [ testStatement ]
		};
		updateSnakValueHtmlForHash( testSnakHash, '<p>Some Html</p>' );
		const apiSpy = jest.spyOn( api, 'get' );
		apiSpy.mockImplementation( ( args ) => {
			if ( args.action === 'wbformatvalue' ) {
				return { result: '<p>FakeData</p>' };
			} else if ( args.action === 'wbformatentities' ) {
				return { wbformatentities: {} };
			}
		} );
		await savedStatementsStore.populateWithClaims( testClaims, true );
		expect( savedStatementsStore.statements.size ).toBe( 1 );
		expect( savedStatementsStore.properties.size ).toBe( 1 );
		expect( apiSpy ).toHaveBeenNthCalledWith( 1, expect.objectContaining( { action: 'wbformatentities', ids: expect.arrayContaining( [ 'P1', 'P2', 'P5' ] ) } ) );
		expect( apiSpy ).toHaveBeenNthCalledWith( 2, expect.objectContaining( { action: 'wbformatvalue', datavalue: expect.stringContaining( '{"value":"test value"' ) } ) );
		expect( apiSpy ).toHaveBeenNthCalledWith( 3, expect.objectContaining( { action: 'wbformatvalue', datavalue: expect.stringContaining( '{"value":"Ofc it\'s a string reference"' ) } ) );
		expect( apiSpy ).toHaveBeenNthCalledWith( 4, expect.objectContaining( { action: 'wbformatvalue', datavalue: expect.stringContaining( '+1999-00-00T00:00:00Z' ) } ) );
	} );

	describe( 'getIndicatorsHtmlForSnakHash', () => {

		it( 'gets HTML set by setIndicatorsHtmlForSnakHash', () => {
			const hash = 'abcd1234';
			const html = '<span class="indicator"></span>';
			setIndicatorsHtmlForSnakHash( hash, html );

			expect( getIndicatorsHtmlForSnakHash( hash ) ).toBe( html );
		} );

		it( 'gets error icon if snak HTML has error', () => {
			const hash = '1234abcd';
			const extensionIndicator = '<span class="indicator-from-extension"></span>';
			setIndicatorsHtmlForSnakHash( hash, extensionIndicator );
			updateSnakValueHtmlForHash( hash, '<div class="cdx-message--error"></div>' );

			expect( getIndicatorsHtmlForSnakHash( hash ) )
				.toBe( '<span class="wikibase-wbui2025-indicator-icon--error"></span>' );
		} );

	} );

	describe( 'getPopoverContentForSnakHash', () => {

		it( 'gets content set by setPopoverContentForSnakHash', () => {
			const hash = 'abcd1234';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForSnakHash( hash, content );

			expect( getPopoverContentForSnakHash( hash ) ).toEqual( content );
		} );

		it( 'prepends custom issue if snak HTML has error', () => {
			const hash = '1234abcd';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForSnakHash( hash, content );
			const errorHtml = '<div class="cdx-message--error"></div>';
			updateSnakValueHtmlForHash( hash, errorHtml );

			const fullContent = getPopoverContentForSnakHash( hash );

			expect( fullContent.slice( 1 ) ).toEqual( content );
			expect( fullContent[ 0 ] ).toEqual( { bodyHtml: errorHtml } );
		} );

	} );
} );
