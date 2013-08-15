# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# Reused and modified from https://github.com/wikimedia/qa-browsertests/blob/master/features/support/modules/url_module.rb
#
# module for URLs

module URL
  def self.client_url(name)
    if ENV['WIKIDATA_CLIENT_URL']
      url = ENV['WIKIDATA_CLIENT_URL']
    else
      url = WIKIDATA_CLIENT_URL
    end
    "#{url}#{name}"
  end

  def self.repo_url(name)
    if ENV['WIKIDATA_REPO_URL']
      url = ENV['WIKIDATA_REPO_URL']
    else
      url = WIKIDATA_REPO_URL
    end
    "#{url}#{name}"
  end

  def self.repo_api()
    if ENV['WIKIDATA_REPO_URL']
      url = ENV['WIKIDATA_REPO_API']
    else
      url = WIKIDATA_REPO_API
    end
    "#{url}"
  end
end
