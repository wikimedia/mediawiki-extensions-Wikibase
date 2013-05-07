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
require 'bundler/setup'
require 'page-object'
require 'page-object/page_factory'
require 'watir-webdriver'
require 'yaml'
require 'require_all'

config = YAML.load_file('config/config.yml')
config.each do |k,v|
  eval("#{k} = '#{v}'")
end

require_all 'features/support/modules'
require_all 'features/support/pages'
require_all 'features/support/utils'

World(PageObject::PageFactory)

def browser(environment, test_name, language)
  local_browser(language)
end

def environment
  :local
end

def local_browser(language)
  if ENV['BROWSER_LABEL']
    browser_label = ENV['BROWSER_LABEL'].to_sym
  else
    browser_label = :firefox
  end

  if language == 'default'
    Watir::Browser.new browser_label
  else
    if browser_label == :firefox
      profile = Selenium::WebDriver::Firefox::Profile.new
    elsif browser_label == :chrome
      profile = Selenium::WebDriver::Chrome::Profile.new
    else
      raise "Changing default language is currently supported only for Firefox and Chrome!"
    end
    profile['intl.accept_languages'] = language
    Watir::Browser.new browser_label, :profile => profile
  end
end

def test_name(scenario)
  if scenario.respond_to? :feature
    "#{scenario.feature.name}: #{scenario.name}"
  elsif scenario.respond_to? :scenario_outline
    "#{scenario.scenario_outline.feature.name}: #{scenario.scenario_outline.name}: #{scenario.name}"
  end
end

Before do |scenario|
  @config = config
  @browser = browser(environment, test_name(scenario), 'default')
end

After do |scenario|
  $session_id = @browser.driver.instance_variable_get(:@bridge).session_id
  @browser.close
end
