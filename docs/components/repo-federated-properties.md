# Federated Properties

Federated Properties is a feature that allows a newly created Wikibase instance to use the existing Properties of another Wikibase. This enables new users evaluating Wikibase to get started without having to spend a lot of time defining basic Properties first.

## Installation

The setting is off by default. To enable Federated Properties from [Wikidata], set <code>$wgWBRepoSettings['federatedPropertiesEnabled'] = true;</code> in your wiki's <code>LocalSettings.php</code>. To configure a different source wiki, the [federatedPropertiesSourceScriptUrl setting] must be set accordingly to the source wiki's script path url, e.g. <code>$wgWBRepoSettings['federatedPropertiesSourceScriptUrl'] = 'https://wikidata.beta.wmflabs.org/w/';</code>.

## Limitations

For now the feature is not intended for production use. It is only meant to facilitate the evaluation of Wikibase as a software for third party use cases.

Federated Properties must only be enabled for a fresh Wikibase installation without any existing local Properties. Local Properties and Federated Properties cannot coexist on a Wikibase at the same time. The setting should be considered permanent after entering any data into the wiki.

[Wikidata]: https://www.wikidata.org/wiki/Wikidata:Main_Page
[federatedPropertiesSourceScriptUrl setting]: @ref repo_federatedPropertiesSourceScriptUrl
