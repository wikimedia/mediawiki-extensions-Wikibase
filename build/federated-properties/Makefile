test:
	ansible-playbook fedProps.yml --limit wikidata-federated-properties.wikidata-dev.eqiad.wmflabs
vm:
	ansible-playbook fedProps.yml --limit wikibase-federated-properties.vm
euspecies:
	ansible-playbook fedProps.yml --limit fedprops-euspecies.wikidata-dev.eqiad.wmflabs
opennext:
	ansible-playbook fedProps.yml --limit fedprops-opennext.wikidata-dev.eqiad.wmflabs
cloud:
	ansible-playbook fedProps.yml --limit "wikidata-federated-properties.wikidata-dev.eqiad.wmflabs,fedprops-euspecies.wikidata-dev.eqiad.wmflabs,fedprops-opennext.wikidata-dev.eqiad.wmflabs"
