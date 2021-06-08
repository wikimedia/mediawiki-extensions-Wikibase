# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# setup & bootstrapping

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
ENV['ITEM_NAMESPACE'] = lenv.lookup(:item_namespace, default: -> { '' })
ENV['PROPERTY_NAMESPACE'] = lenv.lookup(:property_namespace, default: -> { 'Property:' })
ENV['ITEM_ID_PREFIX'] = lenv.lookup(:item_id_prefix, default: -> { 'Q' })
ENV['PROPERTY_ID_PREFIX'] = lenv.lookup(:property_id_prefix, default: -> { 'P' })
ENV['LANGUAGE_CODE'] = lenv.lookup(:language_code, default: -> { 'en' })
ENV['USES_CIRRUS_SEARCH'] = lenv.lookup(:uses_cirrus_search, default: 'false').to_s
ENV['HEADLESS_CAPTURE_PATH'] = nil

require_all File.dirname(__FILE__) + '/modules'
require_all File.dirname(__FILE__) + '/pages'
require_all File.dirname(__FILE__) + '/utils'

PageObject.default_element_wait = 15 # increased to avoid fails on saucelabs

# TODO: find out if this is still needed? is there a real fix wait on it happening instead of sleeping? move this into one of the gems if this is needed, as other can benefit
unless (env_no = ENV['TEST_ENV_NUMBER'].to_i).zero?
  sleep env_no * 4 # sleep time to give webdriver time to setup
end

class DriverJSError < StandardError; end

# Fail on JS errors in browser
AfterStep('~@ignore_browser_errors') do ||
  errors = @browser.driver.manage.logs.get(:browser)
               .select do |e|
                    e.level == 'SEVERE' && e.message.present? && !e.message =~ /favicon.ico.*404/
                  end
               .map(&:message)
               .to_a

  if errors.present?
    raise DriverJSError, errors.join("\n\n")
  end
end
