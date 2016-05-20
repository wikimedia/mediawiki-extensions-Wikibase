# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# Reused and modified from https://github.com/wikimedia/qa-browsertests/blob/master/features/support/env.rb
#
# setup & bootstrapping

# before all
require 'mediawiki_selenium/cucumber'
require 'mediawiki_selenium/pages'
require 'mediawiki_selenium/step_definitions'
require 'mediawiki_selenium'
require 'mediawiki_api/wikidata'
require 'net/http'
require 'active_support/all'
require 'require_all'

lenv = MediawikiSelenium::Environment.load_default
# this used config/config.yml before TODO remove when everything is migrated
ENV['WIKIDATA_REPO_URL'] = lenv.lookup(:mediawiki_url)
ENV['WIKIDATA_REPO_API'] = lenv.lookup(:mediawiki_url_api, default: lambda do
  lenv.lookup(:mediawiki_url)
    .gsub(%r{wiki/$}, 'w/api.php')
    .gsub(%r{index.php/?$}, 'api.php')
end)
# TODO: use user_factory instead
# ENV['WB_REPO_USERNAME'] = lenv.lookup(:mediawiki_user)
ENV['ITEM_NAMESPACE'] = lenv.lookup(:item_namespace, default: -> { '' })
ENV['PROPERTY_NAMESPACE'] = lenv.lookup(:property_namespace, default: -> { 'Property:' })
ENV['ITEM_ID_PREFIX'] = lenv.lookup(:item_id_prefix, default: -> { 'Q' })
ENV['PROPERTY_ID_PREFIX'] = lenv.lookup(:property_id_prefix, default: -> { 'P' })
ENV['LANGUAGE_CODE'] = lenv.lookup(:language_code, default: -> { 'en' })

# require_all 'features/support/modules'
require_all 'features/support/pages'
# require_all 'features/support/utils'

# TODO: remove once everything is migrated
Before('@repo_login') do
  abort('WB_REPO_USERNAME environment variable is not defined! Please export a value for that variable before proceeding.') unless ENV['WB_REPO_USERNAME']
  abort('WB_REPO_PASSWORD environment variable is not defined! Please export a value for that variable before proceeding.') unless ENV['MEDIAWIKI_PASSWORD']
end

PageObject.default_element_wait = 10 # increased to avoid fails on saucelabs

# TODO: find out if this is still needed? is there a real fix wait on it happening instead of sleeping? move this into one of the gems if this is needed, as other can benefit
unless (env_no = ENV['TEST_ENV_NUMBER'].to_i).zero?
  sleep env_no * 4 # sleep time to give webdriver time to setup
end
