# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# bootstrap code and helper functions

$LOAD_PATH.unshift(File.dirname(__FILE__))
$LOAD_PATH.unshift(File.join(File.dirname(__FILE__), ',,', 'lib'))

require 'rspec'
require 'rspec/expectations'
require 'yaml'
require 'watir-webdriver'
require 'page-object'
require 'page-object/page_factory'
require 'require_all'

require_all 'lib'

configs = YAML::load( File.open( 'configuration.yml' ) )
RSpec.configure do |config|
  if(ENV["BROWSER_TYPE"] && SUPPORTED_BROWSERS.include?(ENV["BROWSER_TYPE"]))
    browser_type = ENV["BROWSER_TYPE"]
  elsif configs['DEFAULT_BROWSER']
    browser_type = configs['DEFAULT_BROWSER']
  else
    raise "No default browser defined. Please define DEFAULT_BROWSER in your local configuration.yml!"
  end

  config.exclusion_filter = {}

  if configs['EXPERIMENTAL'] == false
    config.exclusion_filter = config.exclusion_filter.merge({ :experimental => true })
  end
  if browser_type == "firefox"
    config.exclusion_filter = config.exclusion_filter.merge({ :exclude_firefox => true })
  end
  if browser_type == "chrome"
    config.exclusion_filter = config.exclusion_filter.merge({ :exclude_chrome => true })
  end
  if browser_type == "ie"
    config.exclusion_filter = config.exclusion_filter.merge({ :exclude_ie => true })
  end

  config.include PageObject::PageFactory

  config.before(:all) do
    if configs['CONSOLE_LOG']
      puts "\nUsing browser " + browser_type + "."
    end
    if ENV["RUN_REMOTE"] && ENV["RUN_REMOTE"] != ""
      if(ENV["TARGET_OS"])
        target_os = ENV["TARGET_OS"]
      end
      if browser_type == "ie"
        caps = Selenium::WebDriver::Remote::Capabilities.internet_explorer
      elsif browser_type == "chrome"
        caps = Selenium::WebDriver::Remote::Capabilities.chrome
      else
        caps = Selenium::WebDriver::Remote::Capabilities.firefox
      end
      if target_os == "windows"
        caps.platform = :WINDOWS
      elsif target_os == "linux"
        caps.platform = :LINUX
      elsif target_os == "mac"
        caps.platform = :MAC
      end
      if configs['CONSOLE_LOG']
        puts "Running remote on Selenium GRID using OS " + target_os + "."
      end
      @browser = Watir::Browser.new(:remote, :url => REMOTE_SELENIUM_HUB, :desired_capabilities => caps)
      @browser.driver.manage.window.maximize()
    else
      if configs['CONSOLE_LOG']
        puts "Running on local machine."
      end
      @browser = Watir::Browser.new(browser_type)
      @browser.driver.manage.window.maximize()
    end
  end

  config.before(:each) do
    if configs['CONSOLE_LOG']
      puts "\nTest: " +
      example.metadata[:example_group][:example_group][:description_args][0] +
      " => " + example.metadata[:example_group][:description_args][0] +
      " => " + example.metadata[:description_args][0]
    end
  end

  config.after(:all) do
    @browser.close
  end
end

def ajax_wait
  sleep 1
  while (script = @browser.execute_script("return jQuery.active")) != 0 do
    sleep(1.0/3)
  end
  return true
end

# creates a random string
def generate_random_string(length=8)
  chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
  string = ''
  length.times { string << chars[rand(chars.size)] }
  return string
end
