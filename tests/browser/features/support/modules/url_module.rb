# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for URLs
module URL
  def self.client_url(name)
    url_from_base(ENV['WIKIDATA_CLIENT_URL'], name)
  end

  def self.repo_url(name)
    url_from_base(ENV['WIKIDATA_REPO_URL'], name)
  end

  def self.url_from_base(base, name)
    target = "#{base}#{name}"
    param_delimiter = target.include?('?') ? '&' : '?'

    "#{target}#{param_delimiter}setlang=#{ENV['LANGUAGE_CODE']}"
  end

  def self.repo_api
    ENV['WIKIDATA_REPO_API']
  end
end
