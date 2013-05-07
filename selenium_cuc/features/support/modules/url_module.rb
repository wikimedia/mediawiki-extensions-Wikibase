module URL
  def self.client_url(name)
    if ENV['WIKIDATA_CLIENT_URL']
      wikidata_url = ENV['WIKIDATA_CLIENT_URL']
    else
      wikidata_url = WIKIDATA_CLIENT_URL
    end
    "#{wikidata_url}#{name}"
  end

  def self.repo_url(name)
    if ENV['WIKIDATA_REPO_URL']
      wikidata_url = ENV['WIKIDATA_REPO_URL']
    else
      wikidata_url = WIKIDATA_REPO_URL
    end
    "#{wikidata_url}#{name}"
  end
end
