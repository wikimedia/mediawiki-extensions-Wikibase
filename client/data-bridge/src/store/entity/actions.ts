import { StatementMap } from '@wmde/wikibase-datamodel-types';
import Entity from '@/datamodel/Entity';
import EntityRevision from '@/datamodel/EntityRevision';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { EntityState } from './EntityState';
import { Actions, Context, Getters } from 'vuex-smart-module';
import { EntityMutations } from '@/store/entity/mutations';
import { statementModule } from '@/store/statements';
import EntityRevisionWithRedirect from '@/datamodel/EntityRevisionWithRedirect';

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
			.then( ( entityRevision: EntityRevision ) =>
				this.dispatch( 'entityWrite', new EntityRevisionWithRedirect( entityRevision ) ) );
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
			.then( ( entityRevisionWithRedirect: EntityRevisionWithRedirect ) =>
				this.dispatch( 'entityWrite', entityRevisionWithRedirect ) );
	}

	public entityWrite(
		entityRevisionWithRedirect: EntityRevisionWithRedirect,
	): Promise<unknown> {
		this.commit( 'updateRevision', entityRevisionWithRedirect.entityRevision.revisionId );
		this.commit( 'updateEntity', entityRevisionWithRedirect.entityRevision.entity );
		if ( entityRevisionWithRedirect.redirectUrl ) {
			this.commit( 'updateTempUserRedirectUrl', entityRevisionWithRedirect.redirectUrl );
		}

		return this.statementsModule.dispatch( 'initStatements', {
			entityId: entityRevisionWithRedirect.entityRevision.entity.id,
			statements: entityRevisionWithRedirect.entityRevision.entity.statements,
		} );
	}
}
