import Entity from '@/datamodel/Entity';

export default class EntityRevision {
	public readonly entity: Entity;
	public readonly revisionId: number;

	public constructor(
		entity: Entity,
		revisionId: number,
	) {
		this.entity = entity;
		this.revisionId = revisionId;
	}
}
