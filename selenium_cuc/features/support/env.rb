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
require 'net/http'
require 'active_support/all'
require 'rest_client'
require 'require_all'

config = YAML.load_file('config/config.yml')
config.each do |k, v|
  eval("#{k} = '#{v}'")
end

require_all 'features/support/modules'
require_all 'features/support/pages'
require_all 'features/support/utils'

World(PageObject::PageFactory)

def browser(environment, test_name, language)
  if environment == :cloudbees
    sauce_browser(test_name)
  else
    local_browser(language)
  end
end

def environment
  if ENV['SAUCE_ONDEMAND_ACCESS_KEY'] && ENV['SAUCE_ONDEMAND_USERNAME']
    :cloudbees
  else
    :local
  end
end

def sauce_browser(test_name)
  caps = Selenium::WebDriver::Remote::Capabilities.firefox
  caps.version = "23"
  caps.platform = "Windows 7"
  caps[:name] = "#{test_name} #{ENV['JOB_NAME']}##{ENV['BUILD_NUMBER']}"

  require 'selenium/webdriver/remote/http/persistent' # http_client
  browser = Watir::Browser.new(
      :remote,
      :http_client => Selenium::WebDriver::Remote::Http::Persistent.new,
      :url => "http://#{ENV['SAUCE_ONDEMAND_USERNAME']}:#{ENV['SAUCE_ONDEMAND_ACCESS_KEY']}@ondemand.saucelabs.com:80/wd/hub",
      :desired_capabilities => caps)

  browser
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
  $session_id = @browser.driver.instance_variable_get(:@bridge).session_id
end

def sauce_rest(json)
  url = "https://saucelabs.com/rest/v1/#{ENV['SAUCE_ONDEMAND_USERNAME']}/jobs/#{$session_id}"

  RestClient::Request.execute(
      :method => :put,
      :url => url,
      :user => ENV['SAUCE_ONDEMAND_USERNAME'],
      :password => ENV['SAUCE_ONDEMAND_ACCESS_KEY'],
      :headers => {:content_type => "application/json"},
      :payload => json
  )
end

After do |scenario|
  if environment == :cloudbees
    sauce_rest(%Q{{"passed": #{scenario.passed?}}})
    sauce_rest(%Q{{"public": true}})
  end
  @browser.close
end
