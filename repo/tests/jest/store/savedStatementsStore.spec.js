const { setActivePinia, createPinia } = require( 'pinia' );
const { useSavedStatementsStore } = require( '../../../resources/wikibase.wbui2025/store/savedStatementsStore.js' );
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

	it( 'looks up html for snaks with no stored html', () => {
		const savedStatementsStore = useSavedStatementsStore();
		const testClaims = {
			P5: [ testStatement ]
		};
		updateSnakValueHtmlForHash( testSnakHash, '<p>Some Html</p>' );
		const apiSpy = jest.spyOn( api, 'get' );
		apiSpy.mockReturnValue( '<p>FakeData</p>' );
		savedStatementsStore.populateWithClaims( testClaims, true );
		expect( savedStatementsStore.statements.size ).toBe( 1 );
		expect( savedStatementsStore.properties.size ).toBe( 1 );
		expect( apiSpy ).toHaveBeenCalledTimes( 1 );
	} );
} );
