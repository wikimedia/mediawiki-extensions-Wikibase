import EntityRepository from '@/definitions/data-access/EntityRepository';

class ServiceRepositories {
	private entityRepository?: EntityRepository;

	public setEntityRepository( lookup: EntityRepository ): void {
		this.entityRepository = lookup;
	}

	public getEntityRepository(): EntityRepository {
		if ( this.entityRepository ) {
			return this.entityRepository;
		} else {
			throw new Error( 'EntityRepository is undefined' );
		}
	}
}

const services = new ServiceRepositories();

export {
	services,
	ServiceRepositories,
};
