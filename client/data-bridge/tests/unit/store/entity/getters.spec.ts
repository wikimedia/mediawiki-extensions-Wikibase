import { getters } from '@/store/entity/getters';
import newApplicationState from '../newApplicationState';
import newEntityState from './newEntityState';
import {
	ENTITY_ID,
	ENTITY_ONLY_MAIN_STRING_VALUE,
	ENTITY_REVISION,
} from '@/store/entity/getterTypes';
import StatementMap from '@/datamodel/StatementMap';
import Statement from '@/datamodel/Statement';

describe( 'entity/Getters', () => {
	it( 'has an id', () => {
		expect( getters[ ENTITY_ID ](
			newEntityState( { id: 'Q123' } ), null, newApplicationState(), null,
		) ).toBe( 'Q123' );
	} );
	it( 'has a baseRevision id', () => {
		expect( getters[ ENTITY_REVISION ](
			newEntityState( { baseRevision: 23 } ), null, newApplicationState(), null,
		) ).toBe( 23 );
	} );

	describe( ENTITY_ONLY_MAIN_STRING_VALUE, () => {
		function callGetter( statements: StatementMap|null ): string|null {
			const entityState = newEntityState( { statements } );
			return getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
				entityState, null, newApplicationState( {} ), null,
			)( 'P1' );
		}
		const stringStatement: Statement = {
			id: 'statement ID',
			type: 'statement',
			rank: 'normal',
			mainsnak: {
				property: 'P1',
				snaktype: 'value',
				datatype: 'string',
				datavalue: {
					type: 'string',
					value: 'a test string',
				},
			},
		};

		it( 'returns null if uninitialized', () => {
			expect( callGetter( null ) ).toBe( null );
		} );
		it( 'returns value for only string statement', () => {
			const statements = {
				'P1': [ stringStatement ],
			};
			expect( callGetter( statements ) ).toBe( 'a test string' );
		} );
		it( 'throws error for missing statements', () => {
			expect( () => callGetter( {} ) ).toThrow();
			expect( () => callGetter( { 'P1': [] } ) ).toThrow();
		} );
		it( 'throws error for ambiguous statements', () => {
			const statements = {
				'P1': [ stringStatement, stringStatement ],
			};
			expect( () => callGetter( statements ) ).toThrow();
		} );
		it( 'throws error for other snak types', () => {
			function makeStatements( snaktype: 'somevalue' | 'novalue' ): StatementMap {
				return {
					'P1': [ {
						id: 'statement ID',
						type: 'statement',
						rank: 'normal',
						mainsnak: {
							property: 'P1',
							snaktype,
							datatype: 'string',
						},
					} ],
				};
			}
			expect( () => callGetter( makeStatements( 'somevalue' ) ) ).toThrow();
			expect( () => callGetter( makeStatements( 'novalue' ) ) ).toThrow();
		} );
		it( 'throws error for other data value type', () => {
			const statements: StatementMap = {
				'P1': [ {
					id: 'statement ID',
					type: 'statement',
					rank: 'normal',
					mainsnak: {
						property: 'P1',
						snaktype: 'value',
						datatype: 'wikibase-item',
						datavalue: {
							type: 'wikibase-entityid',
							value: {
								'entity-type': 'item',
								id: 'Q42',
							},
						},
					},
				} ],
			};
			expect( () => callGetter( statements ) ).toThrow();
		} );
	} );
} );
