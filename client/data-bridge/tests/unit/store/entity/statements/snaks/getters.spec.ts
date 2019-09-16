import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import StatmentsState from '@/store/entity/statements/StatementsState';
import bindableGetters from '@/store/entity/statements/snaks/getters';
import DataValue from '@/datamodel/DataValue';
import Snak from '@/datamodel/Snak';
import newApplicationState from '../../../newApplicationState';
import newStatementsState from '../newStatementsState';

describe( 'snaks/Getters', () => {
	it( 'returns a bindable getter object', () => {
		const bindable = bindableGetters( {
			dataType: 'testDataType',
			dataValue: 'testDataValue',
			dataValueType: 'testDataValueType',
			snakType: 'testSnakType',
		},
		jest.fn() );

		expect( bindable.testDataType ).toBeDefined();
		expect( typeof ( bindable.testDataType ) ).toBe( 'function' );
		expect( bindable.testDataValue ).toBeDefined();
		expect( typeof ( bindable.testDataValue ) ).toBe( 'function' );
		expect( bindable.testDataValueType ).toBeDefined();
		expect( typeof ( bindable.testDataValueType ) ).toBe( 'function' );
		expect( bindable.testSnakType ).toBeDefined();
		expect( typeof ( bindable.testSnakType ) ).toBe( 'function' );

	} );

	describe( 'bounded', () => {
		let returnSnak: Snak|null;
		const traveler = jest.fn( () => {
			return returnSnak;
		} );

		const getters: GetterTree<StatmentsState, Application> = bindableGetters( {
			dataType: 'testDataType',
			dataValue: 'testDataValue',
			dataValueType: 'testDataValueType',
			snakType: 'testSnakType',
		},
		traveler );

		describe( 'snaktype', () => {
			it( 'calls the traveler function to determine the snak', () => {
				const map = newStatementsState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {
							property: 'P42',
							snaktype: 'somevalue',
							datatype: 'url',
						},
					} ],
				} } );

				getters.testSnakType( map, null, newApplicationState(), null )( null );

				expect( traveler ).toHaveBeenCalledTimes( 1 );
				expect( traveler ).toHaveBeenCalledWith( map, null );
			} );

			it( 'has a snaktype', () => {
				returnSnak = {
					property: 'P42',
					snaktype: 'somevalue',
					datatype: 'url',
				};

				expect( getters.testSnakType(
					{}, null, newApplicationState(), null,
				)( null ) ).toBe( returnSnak.snaktype );
			} );

			it( 'return null if snak was not found', () => {
				returnSnak = null;

				expect( getters.testSnakType(
					{}, null, newApplicationState(), null,
				)( null ) ).toBeNull();
			} );
		} );

		describe( 'datatype', () => {
			it( 'calls the traveler function to determine the snak', () => {
				const map = newStatementsState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {
							property: 'P42',
							snaktype: 'somevalue',
							datatype: 'url',
						},
					} ],
				} } );

				getters.testSnakType( map, null, newApplicationState(), null )( null );

				expect( traveler ).toHaveBeenCalledTimes( 1 );
				expect( traveler ).toHaveBeenCalledWith( map, null );
			} );

			it( 'returns the datatype', () => {
				returnSnak = {
					property: 'P42',
					snaktype: 'somevalue',
					datatype: 'url',
				};

				expect( getters.testDataType(
					{}, null, newApplicationState(), null,
				)( null ) ).toBe( returnSnak.datatype );
			} );

			it( 'returns null if snak was not found', () => {
				returnSnak = null;

				expect( getters.testDataType(
					{}, null, newApplicationState(), null,
				)( null ) ).toBeNull();
			} );
		} );

		describe( 'datavalues', () => {
			describe( 'datavaluetype', () => {
				it( 'calls the traveler function to determine the snak', () => {
					const map = newStatementsState( { Q42: {
						P42: [ {
							type: 'statement',
							id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
							rank: 'normal',
							mainsnak: {
								property: 'P42',
								snaktype: 'somevalue',
								datatype: 'url',
							},
						} ],
					} } );

					getters.testSnakType( map, null, newApplicationState(), null )( null );

					expect( traveler ).toHaveBeenCalledTimes( 1 );
					expect( traveler ).toHaveBeenCalledWith( map, null );
				} );

				it( 'contains a datavaluetype', () => {
					const datavalue: DataValue = {
						type: 'string',
						value: 'I am a string',
					};

					returnSnak = {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
						datavalue,
					};

					expect( getters.testDataValueType(
						{}, null, newApplicationState(), null,
					)( null ) ).toStrictEqual( datavalue.type );
				} );

				it( 'returns null if snak was not found', () => {
					returnSnak = null;

					expect( getters.testDataValueType(
						{}, null, newApplicationState(), null,
					)( null ) ).toBeNull();
				} );
			} );

			describe( 'datavalue value', () => {
				it( 'calls the traveler function to determine the snak', () => {
					const map = newStatementsState( { Q42: {
						P42: [ {
							type: 'statement',
							id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
							rank: 'normal',
							mainsnak: {
								property: 'P42',
								snaktype: 'somevalue',
								datatype: 'url',
							},
						} ],
					} } );

					getters.testSnakType( map, null, newApplicationState(), null )( null );

					expect( traveler ).toHaveBeenCalledTimes( 1 );
					expect( traveler ).toHaveBeenCalledWith( map, null );
				} );

				it( 'contains a value', () => {
					const datavalue: DataValue = {
						type: 'string',
						value: 'I am a string',
					};

					returnSnak = {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
						datavalue,
					};

					expect( getters.testDataValue(
						{}, null, newApplicationState(), null,
					)( null ) ).toStrictEqual( datavalue );
				} );

				it( 'returns null if is no datavalue', () => {
					returnSnak = {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
					};

					expect( getters.testDataValue(
						{}, null, newApplicationState(), null,
					)( null ) ).toBeNull();
				} );

				it( 'returns null if the snak was not found', () => {
					returnSnak = null;

					expect( getters.testDataValue(
						{}, null, newApplicationState(), null,
					)( null ) ).toBeNull();
				} );
			} );
		} );
	} );
} );
