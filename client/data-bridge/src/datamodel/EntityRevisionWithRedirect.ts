import EntityRevision from '@/datamodel/EntityRevision';

export default class EntityRevisionWithRedirect {

	public readonly entityRevision: EntityRevision;
	public readonly redirectUrl: URL | undefined;

	public constructor(
		entityRevision: EntityRevision,
		redirectUrl: URL | undefined = undefined,
	) {
		this.entityRevision = entityRevision;
		this.redirectUrl = redirectUrl;
	}

}
