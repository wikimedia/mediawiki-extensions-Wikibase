jest.mock(
	'../../../resources/wikibase.wbui2025/repoSettings.json',
	() => ( {
		viewUiTags: []
	} ),
	{ virtual: true }
);

const { setActivePinia, createPinia } = require( 'pinia' );
const {
	getIndicatorHtmlForMainSnak,
	setIndicatorHtmlForMainSnak,
	getIndicatorHtmlForQualifier,
	setIndicatorHtmlForQualifier,
	getIndicatorHtmlForReferenceSnak,
	setIndicatorHtmlForReferenceSnak,
	setPopoverContentForMainSnak,
	getPopoverContentForMainSnak,
	setPopoverContentForQualifier,
	getPopoverContentForQualifier,
	setPopoverContentForReferenceSnak,
	getPopoverContentForReferenceSnak,
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

	describe( 'getIndicatorHtmlForMainSnak', () => {

		it( 'gets HTML set by setIndicatorHtmlForMainSnak', () => {
			const statementId = 'Q63214323$c039df2c-4de7-9e9d-dea7-cfd21ace7964';
			const html = '<span class="indicator"></span>';
			setIndicatorHtmlForMainSnak( statementId, html );

			expect( getIndicatorHtmlForMainSnak( statementId ) ).toBe( html );
		} );

		it( 'gets error icon if snak HTML has error', () => {
			const statementId = 'Q63214322$1a2b6fd0-4c96-af83-34b4-9822019671ac';
			const hash = '8648aa305f74e41867e7d97fb5bb57e705e4e90f';
			const extensionIndicator = '<span class="indicator-from-extension"></span>';
			setIndicatorHtmlForMainSnak( statementId, extensionIndicator );
			updateSnakValueHtmlForHash( hash, '<div class="cdx-message--error"></div>' );
			useSavedStatementsStore().populateWithClaims( {
				P276: [ { id: statementId, mainsnak: { hash } } ]
			} );

			expect( getIndicatorHtmlForMainSnak( statementId ) )
				.toBe( '<span class="wikibase-wbui2025-indicator-icon--error"></span>' );
		} );

	} );

	describe( 'getIndicatorHtmlForQualifier', () => {

		it( 'gets HTML set by setIndicatorHtmlForQualifier', () => {
			const statementId = 'Q63214320$a80ca355-47ba-eb55-aa60-70e03f834280';
			const hash = '7017c34a4452ccc089c1156154b3512c996afc28';
			const html = '<span class="indicator"></span>';
			setIndicatorHtmlForQualifier( statementId, hash, html );

			expect( getIndicatorHtmlForQualifier( statementId, hash ) ).toBe( html );
		} );

		it( 'gets error icon if snak HTML has error', () => {
			const statementId = 'Q63214320$ba1717c9-4b05-856d-e0bf-213c7a960bcd';
			const hash = '71a1be5d30467ce23cd4a49f28e0093a0d45405b';
			const extensionIndicator = '<span class="indicator-from-extension"></span>';
			setIndicatorHtmlForQualifier( statementId, hash, extensionIndicator );
			updateSnakValueHtmlForHash( hash, '<div class="cdx-message--error"></div>' );

			expect( getIndicatorHtmlForQualifier( statementId, hash ) )
				.toBe( '<span class="wikibase-wbui2025-indicator-icon--error"></span>' );
		} );

	} );

	describe( 'getIndicatorHtmlForReferenceSnak', () => {

		it( 'gets HTML set by setIndicatorHtmlForReferenceSnak', () => {
			const statementId = 'Q63214321$4fddc663-4a05-f00c-67e5-e6985c36a846';
			const referenceHash = '8d7792bd82d8ffe92fad03e404a350925229675f';
			const snakHash = 'a98fc44bad100bd01eea123456c48adaaf448187';
			const html = '<span class="indicator"></span>';
			setIndicatorHtmlForReferenceSnak( statementId, referenceHash, snakHash, html );

			expect( getIndicatorHtmlForReferenceSnak( statementId, referenceHash, snakHash ) ).toBe( html );
		} );

		it( 'gets error icon if snak HTML has error', () => {
			const statementId = 'Q28053831$1B3B093D-AA59-4445-9665-6E2F39108B25';
			const referenceHash = '1b02c8bb91895f0ff1057f045e26cde2000e2630';
			const snakHash = '5d3b16d350189b0a81818758208505444c86c127';
			const extensionIndicator = '<span class="indicator-from-extension"></span>';
			setIndicatorHtmlForReferenceSnak( statementId, referenceHash, snakHash, extensionIndicator );
			updateSnakValueHtmlForHash( snakHash, '<div class="cdx-message--error"></div>' );

			expect( getIndicatorHtmlForReferenceSnak( statementId, referenceHash, snakHash ) )
				.toBe( '<span class="wikibase-wbui2025-indicator-icon--error"></span>' );
		} );

	} );

	describe( 'getPopoverContentForMainSnak', () => {

		it( 'gets content set by setPopoverContentForMainSnak', () => {
			const statementId = 'Q47544489$8b0d1c06-4407-e6ca-5a0b-f2fd0b3e9f60';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForMainSnak( statementId, content );

			expect( getPopoverContentForMainSnak( statementId ) ).toEqual( content );
		} );

		it( 'prepends custom issue if snak HTML has error', () => {
			const statementId = 'Q28053831$2fd209b9-493e-b034-c3e4-dd419c1d7e07';
			const hash = '1f6fb8bd61b1d758e21b9b7a2045fdacef6c74ea';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForMainSnak( statementId, content );
			const errorHtml = '<div class="cdx-message--error"></div>';
			updateSnakValueHtmlForHash( hash, errorHtml );
			useSavedStatementsStore().populateWithClaims( {
				P276: [ { id: statementId, mainsnak: { hash } } ]
			} );

			const fullContent = getPopoverContentForMainSnak( statementId );

			expect( fullContent.slice( 1 ) ).toEqual( content );
			expect( fullContent[ 0 ] ).toEqual( { bodyHtml: errorHtml } );
		} );

	} );

	describe( 'getPopoverContentForQualifier', () => {

		it( 'gets content set by setPopoverContentForQualifier', () => {
			const statementId = 'Q28053831$5bce4b92-4f6b-5d77-3fdc-9631acb11667';
			const hash = 'a46995c4fd7a79625660f79c1b0f1ef97673efa8';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForQualifier( statementId, hash, content );

			expect( getPopoverContentForQualifier( statementId, hash ) ).toEqual( content );
		} );

		it( 'prepends custom issue if snak HTML has error', () => {
			const statementId = 'Q30087264$65bc4730-4d80-b986-5b7e-07aa3b89fe08';
			const hash = 'a3c7892410c7aae6a58ba45cde6d8e1a37ff4bf7';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForQualifier( statementId, hash, content );
			const errorHtml = '<div class="cdx-message--error"></div>';
			updateSnakValueHtmlForHash( hash, errorHtml );

			const fullContent = getPopoverContentForQualifier( statementId, hash );

			expect( fullContent.slice( 1 ) ).toEqual( content );
			expect( fullContent[ 0 ] ).toEqual( { bodyHtml: errorHtml } );
		} );

	} );

	describe( 'getPopoverContentForReferenceSnak', () => {

		it( 'gets content set by setPopoverContentForReferenceSnak', () => {
			const statementId = 'Q123481141$fff26438-4793-4f8a-c406-ce03de58aa0d';
			const referenceHash = '1f4ed5c6c6a6403b7c15027502f577d4760548d2';
			const snakHash = '1e254537080260fbbed315d88de6f7a9c68d9349';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForReferenceSnak( statementId, referenceHash, snakHash, content );

			expect( getPopoverContentForReferenceSnak( statementId, referenceHash, snakHash ) ).toEqual( content );
		} );

		it( 'prepends custom issue if snak HTML has error', () => {
			const statementId = 'Q123481141$2ed3548d-44fe-a1da-2fe1-a24634f77a97';
			const referenceHash = '1f4ed5c6c6a6403b7c15027502f577d4760548d2';
			const snakHash = '1e254537080260fbbed315d88de6f7a9c68d9349';
			const content = [ { bodyHtml: 'html 1' }, { bodyHtml: 'html 2' } ];
			setPopoverContentForReferenceSnak( statementId, referenceHash, snakHash, content );
			const errorHtml = '<div class="cdx-message--error"></div>';
			updateSnakValueHtmlForHash( snakHash, errorHtml );

			const fullContent = getPopoverContentForReferenceSnak( statementId, referenceHash, snakHash );

			expect( fullContent.slice( 1 ) ).toEqual( content );
			expect( fullContent[ 0 ] ).toEqual( { bodyHtml: errorHtml } );
		} );

	} );
} );
