import {
	DataType,
	DataValue,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import { StatementActions } from '@/store/statements/actions';
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
							datatype: 'wikibase-item' as DataType,
							datavalue: {
								value: {
									'entity-type': 'item',
									id: 'Q6342720',
								},
								type: 'wikibase-entityid',
							} as unknown as DataValue,
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
