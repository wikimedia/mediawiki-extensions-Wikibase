import {
	Snak,
	Statement,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import { StatementMutations } from '@/store/statements/mutations';
import newStatementState from './newStatementState';
import { inject } from 'vuex-smart-module';

describe( 'statements/Mutations', () => {

	describe( 'general mutations on a statement', () => {

		describe( 'setStatements', () => {
			it( 'sets a new statement', () => {
				const state = newStatementState();

				const statements: StatementMap = {
					P42: [ {
						type: 'statement',
						id: 'Q242$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {} as Snak,
					} ],
				};

				const mutations = inject( StatementMutations, { state } );

				mutations.setStatements( { entityId: 'Q42', statements } );
				expect( state ).toStrictEqual( { Q42: statements } );
			} );
		} );
	} );

	describe( 'reset', () => {
		it( 'removes all statements', () => {
			const state = newStatementState( {
				'Q1': {
					'P123': [ {} as Statement, {} as Statement ],
					'P456': [ {} as Statement ],
				},
				'Q2': {
					'P12': [ {} as Statement ],
				},
			} );
			const mutations = inject( StatementMutations, { state } );

			mutations.reset();

			expect( Object.keys( state ) ).toStrictEqual( [] );
		} );
	} );

} );
