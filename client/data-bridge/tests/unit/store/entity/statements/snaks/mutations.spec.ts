import { MutationTree } from 'vuex';
import bindableMutations from '@/store/entity/statements/snaks/mutations';
import Snak from '@/datamodel/Snak';
import StatmentsState from '@/store/entity/statements/StatementsState';
import newStatementsState from '../newStatementsState';

describe( 'snaks/Mutations', () => {
	it( 'returns a bindable mutation object', () => {
		const bindable = bindableMutations( {
			setDataValue: 'setTestDataValue',
			setSnakType: 'setTestSnakType',
		},
		jest.fn() );

		expect( bindable.setTestDataValue ).toBeDefined();
		expect( typeof ( bindable.setTestDataValue ) ).toBe( 'function' );
		expect( bindable.setTestSnakType ).toBeDefined();
		expect( typeof ( bindable.setTestSnakType ) ).toBe( 'function' );
	} );

	describe( 'bounded', () => {
		let returnSnak: Snak|null;
		const traveler = jest.fn( () => {
			return returnSnak;
		} );

		const mutations: MutationTree<StatmentsState> = bindableMutations( {
			setDataValue: 'setTestDataValue',
			setSnakType: 'setTestSnakType',
		},
		traveler );

		describe( 'setDataValue', () => {
			it( 'calls the traveler function to determine the snak', () => {
				const map = newStatementsState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {
							property: 'P42',
							snaktype: 'value',
							datatype: 'string',
						},
					} ],
				} } );

				returnSnak = map.Q42.P42[ 0 ].mainsnak;

				const path = {
					propertyId: 'P42',
					index: 0,
				};

				mutations.setTestDataValue( map, {
					path,
					value: {
						type: 'string',
						value: 'a string',
					},
				} );

				expect( traveler ).toHaveBeenCalledTimes( 1 );
				expect( traveler ).toHaveBeenCalledWith( map, path );
			} );

			it( 'sets new datavalue', () => {
				const mainsnak: Snak = {
					property: 'P42',
					snaktype: 'novalue',
					datatype: 'string',
				};

				const map = newStatementsState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak,
					} ],
				} } );

				returnSnak = mainsnak;

				const value = {
					type: 'string',
					value: 'a new string',
				};

				mutations.setTestDataValue( map, {
					path: null,
					value,
				} );

				expect( map.Q42.P42[ 0 ].mainsnak.datavalue ).toStrictEqual( value );
			} );

			it( 'overwrites old datavalue', () => {
				const mainsnak: Snak = {
					property: 'P42',
					snaktype: 'value',
					datavalue: {
						type: 'string',
						value: 'old string',
					},
					datatype: 'string',
				};

				const map = newStatementsState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak,
					} ],
				} } );

				returnSnak = mainsnak;

				const value = {
					type: 'string',
					value: 'a new string',
				};

				mutations.setTestDataValue( map, {
					path: null,
					value,
				} );

				expect( map.Q42.P42[ 0 ].mainsnak.datavalue ).toStrictEqual( value );
			} );
		} );

		describe( 'setTestSnakType', () => {
			it( 'calls the traveler function to determine the snak', () => {
				const map = newStatementsState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {
							property: 'P42',
							snaktype: 'value',
							datatype: 'string',
						},
					} ],
				} } );

				returnSnak = map.Q42.P42[ 0 ].mainsnak;

				const path = {
					propertyId: 'P42',
					index: 0,
				};

				mutations.setTestSnakType( map, {
					path,
					value: {
						type: 'string',
						value: 'a string',
					},
				} );

				expect( traveler ).toHaveBeenCalledTimes( 1 );
				expect( traveler ).toHaveBeenCalledWith( map, path );
			} );

			it( 'sets new snak type', () => {
				const mainsnak: Snak = {
					property: 'P42',
					snaktype: 'novalue',
					datatype: 'string',
				};

				const map = newStatementsState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak,
					} ],
				} } );

				returnSnak = mainsnak;
				const value = 'value';

				mutations.setTestSnakType( map, {
					path: null,
					value,
				} );

				expect( map.Q42.P42[ 0 ].mainsnak.snaktype ).toStrictEqual( value );
			} );
		} );
	} );
} );
