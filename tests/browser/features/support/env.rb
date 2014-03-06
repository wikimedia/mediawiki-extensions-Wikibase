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
require "mediawiki_selenium"

require "net/http"
require "active_support/all"
require "require_all"

config = YAML.load_file("config/config.yml")
config.each do |k, v|
  eval("#{k} = '#{v}'")
end

require_all "features/support/modules"
require_all "features/support/pages"
require_all "features/support/utils"

Before("@repo_login") do
  abort("WB_REPO_USERNAME environment variable is not defined! Please export a value for that variable before proceeding.") unless ENV["WB_REPO_USERNAME"]
  abort("WB_REPO_PASSWORD environment variable is not defined! Please export a value for that variable before proceeding.") unless ENV["WB_REPO_PASSWORD"]
end
