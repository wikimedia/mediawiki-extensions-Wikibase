import { StatementMap } from '@wmde/wikibase-datamodel-types';
import { Actions } from 'vuex-smart-module';
import { StatementState } from './StatementState';
import EntityId from '@/datamodel/EntityId';
import { StatementMutations } from '@/store/statements/mutations';
import { StatementGetters } from '@/store/statements/getters';

export class StatementActions extends Actions<
StatementState,
StatementGetters,
StatementMutations,
StatementActions
> {
	public initStatements(
		payload: {
			entityId: EntityId;
			statements: StatementMap;
		},
	): Promise<void> {
		this.commit( 'setStatements', {
			entityId: payload.entityId,
			statements: payload.statements,
		} );
		return Promise.resolve();
	}

}
