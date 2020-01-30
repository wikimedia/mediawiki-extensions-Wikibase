import { StatementState } from '@/store/statements';
import { Mutations } from 'vuex-smart-module';
import { STATEMENTS_SET } from '@/store/statements/mutationTypes';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import Vue from 'vue';
import { PayloadSnakDataValue, PayloadSnakType } from '@/store/statements/snaks/Payloads';
import Snak from '@/datamodel/Snak';
import { SNAK_SET_DATA_VALUE, SNAK_SET_SNAKTYPE } from '@/store/statements/snaks/mutationTypes';
import { PathToSnak } from '@/store/statements/PathToSnak';

export class StatementMutations extends Mutations<StatementState> {
	public [ STATEMENTS_SET ](
		payload: { entityId: EntityId; statements: StatementMap },
	): void {
		Vue.set( this.state, payload.entityId, payload.statements );
	}

	public [ SNAK_SET_DATA_VALUE ]( payload: PayloadSnakDataValue<PathToSnak> ): void {
		const snak = payload.path.resolveSnakInStatement( this.state );
		Vue.set( snak as Snak, 'datavalue', payload.value );
	}

	public [ SNAK_SET_SNAKTYPE ]( payload: PayloadSnakType<PathToSnak> ): void {
		const snak = payload.path.resolveSnakInStatement( this.state );
		( snak as Snak ).snaktype = payload.value;
	}
}
