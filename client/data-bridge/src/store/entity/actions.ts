import { StatementMap } from '@wmde/wikibase-datamodel-types';
import Entity from '@/datamodel/Entity';
import EntityRevision from '@/datamodel/EntityRevision';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { EntityState } from './EntityState';
import { Actions, Context, Getters } from 'vuex-smart-module';
import { EntityMutations } from '@/store/entity/mutations';
import { statementModule } from '@/store/statements';

export class EntityActions extends Actions<EntityState, Getters<EntityState>, EntityMutations, EntityActions> {
	private store!: Store<Application>;
	private statementsModule!: Context<typeof statementModule>;

	public $init( store: Store<Application> ): void {
		this.store = store;
		this.statementsModule = statementModule.context( store );
	}

	public entityInit(
		payload: { entity: string },
	): Promise<unknown> {
		return this.store.$services.get( 'readingEntityRepository' )
			.getEntity( payload.entity )
			.then( ( entityRevision: EntityRevision ) => this.dispatch( 'entityWrite', entityRevision ) );
	}

	public entitySave(
		payload: { statements: StatementMap; assertUser?: boolean },
	): Promise<unknown> {
		const entityId = this.state.id;
		const entity = new Entity( entityId, payload.statements );
		const base = new EntityRevision(
			new Entity( entityId, this.statementsModule.state[ entityId ] ),
			this.state.baseRevision,
		);

		return this.store.$services.get( 'writingEntityRepository' )
			.saveEntity( entity, base, payload.assertUser )
			.then( ( entityRevision: EntityRevision ) => this.dispatch( 'entityWrite', entityRevision ) );
	}

	public entityWrite(
		entityRevision: EntityRevision,
	): Promise<unknown> {
		this.commit( 'updateRevision', entityRevision.revisionId );
		this.commit( 'updateEntity', entityRevision.entity );

		return this.statementsModule.dispatch( 'initStatements', {
			entityId: entityRevision.entity.id,
			statements: entityRevision.entity.statements,
		} );
	}
}
