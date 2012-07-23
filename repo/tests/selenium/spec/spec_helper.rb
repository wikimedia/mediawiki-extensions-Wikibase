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

require_all 'lib/pages'
configs = YAML::load( File.open( 'configuration.yml' ) )
RSpec.configure do |config|
  config.include PageObject::PageFactory
  config.before(:all) do
    if ENV["BROWSER_TYPE"]
      BROWSER_TYPE = ENV["BROWSER_TYPE"]
    elsif configs['DEFAULT_BROWSER']
      BROWSER_TYPE = configs['DEFAULT_BROWSER']
    else
      raise "No default browser defined. Please define DEFAULT_BROWSER in your local configuration.yml!"
    end
    @browser = Watir::Browser.new(BROWSER_TYPE)
  end

  config.after(:all) do
    @browser.close
  end

end

def ajax_wait
  # TODO: forced sleep for chrome & opera ('cause jQuery.active not working) is not that nice => investigate other possibilities
  if ($target_browser == "chrome") || ($target_browser == "opera") || ($target_browser == "ie")
    sleep 1
  end
  while (script = @browser.execute_script("return jQuery.active")) == 1 do
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
