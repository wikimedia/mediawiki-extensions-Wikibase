import { StatementState } from '@/store/statements';
import { Mutations } from 'vuex-smart-module';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import Vue from 'vue';
import clone from '@/store/clone';

export class StatementMutations extends Mutations<StatementState> {
	public setStatements(
		payload: { entityId: EntityId; statements: StatementMap },
	): void {
		Vue.set( this.state, payload.entityId, clone( payload.statements ) );
	}
}
