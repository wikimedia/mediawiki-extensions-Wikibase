# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for URLs
module URL
  def self.client_url(name)
    url = ENV['WIKIDATA_CLIENT_URL']
    lang = ENV['LANGUAGE_CODE']
    "#{url}#{name}?setlang=#{lang}"
  end

  def self.repo_url(name)
    url = ENV['WIKIDATA_REPO_URL']
    lang = ENV['LANGUAGE_CODE']
    "#{url}#{name}?setlang=#{lang}"
  end

  def self.repo_api
    url = ENV['WIKIDATA_REPO_API']
    "#{url}"
  end
end
