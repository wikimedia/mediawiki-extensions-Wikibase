import newMockStore from '../../../newMockStore';
import SnakErrors from '@/definitions/storeActionErrors/SnakActionErrors';
import Snak from '@/datamodel/Snak';
import bindableActions from '@/store/entity/statements/snaks/actions';

describe( 'snak/actions', () => {
	it( 'returns a bindable action object', () => {
		const bindable = bindableActions( {
			setStringDataValue: 'setTestStringDataValue',
		}, {
			setDataValue: 'setTestDataValue',
			setSnakType: 'setTestSnakType',
		},
		jest.fn() );

		expect( bindable.setTestStringDataValue ).toBeDefined();
		expect( typeof ( bindable.setTestStringDataValue ) ).toBe( 'function' );
	} );

	describe( 'bounded', () => {
		let returnSnak: Snak|null;
		const traveler = jest.fn( () => {
			return returnSnak;
		} );

		const setDataValue = 'setTestDataValue';
		const setSnakType = 'setTestSnakType';

		const actions = bindableActions( {
			setStringDataValue: 'setTestStringDataValue',
		}, {
			setDataValue,
			setSnakType,
		},
		traveler );

		describe( 'setStringDataValue', () => {
			describe( 'traveler use', () => {
				it( 'calls the traveler function to determine the snak', () => {
					const context = newMockStore( {
						state: {
							Q42: {
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
							},
						},
					} );

					returnSnak = context.state.Q42.P42[ 0 ].mainsnak;

					return ( actions as any ).setTestStringDataValue(
						context,
						{
							path: null,
							value: {
								type: 'string',
								value: 'a string',
							},
						},
					).then( () => {
						expect( traveler ).toHaveBeenCalledTimes( 1 );
						expect( traveler ).toHaveBeenCalledWith( context.state, null );
					} );
				} );

				it( 'rejects if the snak was not found', async () => {
					const context = newMockStore( {
						state: {
							Q42: {
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
							},
						},
					} );

					returnSnak = null;

					await expect(
						( actions as any ).setTestStringDataValue(
							context,
							{
								path: null,
								value: {
									type: 'string',
									value: 'a string',
								},
							},
						).catch( ( error: Error ) => {
							expect( traveler ).toHaveBeenCalledTimes( 1 );
							expect( traveler ).toHaveBeenCalledWith( context.state, null );
							expect( error.message ).toBe( SnakErrors.NO_SNAK_FOUND );
							throw new Error();
						} ),
					).rejects.toBeDefined();
				} );
			} );

			it( 'rejects if the data value type of the input is not string', async () => {
				const context = newMockStore( {
					state: {
						Q42: {
							P42: [ {
								type: 'statement',
								id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
								rank: 'normal',
								mainsnak: {
									property: 'P42',
									snaktype: 'value',
									datatype: 'string',
									datavalue: {},
								},
							} ],
						},
					},
				} );

				returnSnak = context.state.Q42.P42[ 0 ].mainsnak;

				await expect(
					( actions as any ).setTestStringDataValue(
						context,
						{
							path: null,
							value: {
								type: 'url',
								value: 'url',
							},
						},
					).catch( ( error: Error ) => {
						expect( traveler ).toHaveBeenCalledTimes( 1 );
						expect( traveler ).toHaveBeenCalledWith( context.state, null );
						expect( error.message ).toBe( SnakErrors.WRONG_PAYLOAD_TYPE );
						throw new Error();
					} ),
				).rejects.toBeDefined();
			} );

			it( 'rejects if the data value of the input is not string', async () => {
				const context = newMockStore( {
					state: {
						Q42: {
							P42: [ {
								type: 'statement',
								id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
								rank: 'normal',
								mainsnak: {
									property: 'P42',
									snaktype: 'value',
									datatype: 'string',
									datavalue: {},
								},
							} ],
						},
					},
				} );

				returnSnak = context.state.Q42.P42[ 0 ].mainsnak;

				await expect(
					( actions as any ).setTestStringDataValue(
						context,
						{
							path: null,
							value: {
								type: 'string',
								value: 42,
							},
						},
					).catch( ( error: Error ) => {
						expect( traveler ).toHaveBeenCalledTimes( 1 );
						expect( traveler ).toHaveBeenCalledWith( context.state, null );
						expect( error.message ).toBe( SnakErrors.WRONG_PAYLOAD_VALUE_TYPE );
						throw new Error();
					} ),
				).rejects.toBeDefined();
			} );

			it( 'commits to setStringDataValue', () => {
				const commit = jest.fn();
				const context = newMockStore( {
					commit,
					state: {
						Q42: {
							P42: [ {
								type: 'statement',
								id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
								rank: 'normal',
								mainsnak: {
									property: 'P42',
									snaktype: 'novalue',
									datatype: 'string',
								},
							} ],
						},
					},
				} );

				returnSnak = context.state.Q42.P42[ 0 ].mainsnak;

				const payload = {
					path: null,
					value: {
						type: 'string',
						value: 'Töfften',
					},
				};

				return ( actions as any ).setTestStringDataValue(
					context,
					payload,
				).then( () => {
					expect( commit ).toHaveBeenCalledTimes( 2 );
					expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( setSnakType );
					expect( commit.mock.calls[ 0 ][ 1 ] ).toStrictEqual( { path: null, value: 'value' } );
				} );
			} );

			it( 'commits to setStringDataValue and resolves', () => {
				const commit = jest.fn();
				const context = newMockStore( {
					commit,
					state: {
						Q42: {
							P42: [ {
								type: 'statement',
								id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
								rank: 'normal',
								mainsnak: {
									property: 'P42',
									snaktype: 'value',
									datatype: 'string',
									datavalue: {},
								},
							} ],
						},
					},
				} );

				const payload = {
					path: null,
					value: {
						type: 'string',
						value: 'Töfften',
					},
				};

				returnSnak = context.state.Q42.P42[ 0 ].mainsnak;

				return ( actions as any ).setTestStringDataValue(
					context,
					payload,
				).then( () => {
					expect( commit ).toHaveBeenCalledTimes( 2 );
					expect( commit.mock.calls[ 1 ][ 0 ] ).toBe( setDataValue );
					expect( commit.mock.calls[ 1 ][ 1 ] ).toBe( payload );
				} );
			} );
		} );
	} );
} );
