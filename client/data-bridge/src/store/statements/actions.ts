import { Actions } from 'vuex-smart-module';
import { StatementState } from '@/store/statements';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
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
