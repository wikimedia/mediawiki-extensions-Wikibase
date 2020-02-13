import { StatementState } from '@/store/statements';
import { Mutations } from 'vuex-smart-module';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import Vue from 'vue';
import { PayloadSnakDataValue, PayloadSnakType } from '@/store/statements/snaks/Payloads';
import Snak from '@/datamodel/Snak';
import { PathToSnak } from '@/store/statements/PathToSnak';
import clone from '@/store/clone';

export class StatementMutations extends Mutations<StatementState> {
	public setStatements(
		payload: { entityId: EntityId; statements: StatementMap },
	): void {
		Vue.set( this.state, payload.entityId, clone( payload.statements ) );
	}

	public setDataValue( payload: PayloadSnakDataValue<PathToSnak> ): void {
		const snak = payload.path.resolveSnakInStatement( this.state );
		Vue.set( snak as Snak, 'datavalue', payload.value );
	}

	public setSnakType( payload: PayloadSnakType<PathToSnak> ): void {
		const snak = payload.path.resolveSnakInStatement( this.state );
		( snak as Snak ).snaktype = payload.value;
	}
}
