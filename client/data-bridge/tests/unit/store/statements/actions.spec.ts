import { StatementActions } from '@/store/statements/actions';
import StatementMap from '@/datamodel/StatementMap';
import { inject } from 'vuex-smart-module';

describe( 'statement actions', () => {
	describe( 'initStatements', () => {
		it( 'commits to setStatements', () => {
			const payload = {
				entityId: 'Q42',
				statements: {
					P23: [ {
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						mainsnak: {
							snaktype: 'value',
							property: 'P23',
							datatype: 'wikibase-item',
							datavalue: {
								value: {
									'entity-type': 'item',
									id: 'Q6342720',
								},
								type: 'wikibase-entityid',
							},
						},
						type: 'statement',
						rank: 'normal',
					} ],
				} as StatementMap,
			};

			const commit = jest.fn();
			const actions = inject( StatementActions, {
				commit,
			} );

			actions.initStatements( payload );

			expect( commit ).toHaveBeenCalledWith(
				'setStatements',
				payload,
			);
		} );
	} );

} );
