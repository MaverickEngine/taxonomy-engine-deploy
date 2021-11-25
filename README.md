# TaxonomyEngine

Categorise your WordPress content with the assistance of machine learning and crowdsourcing

* This plugin is in early development. Use at own risk.

## What does it do?

TaxonomyEngine provides a *process* for _reliably_ tagging some of your existing content on your Wordpress website, trains a machine learning model based on your tagging, and then automagically tags the rest of your content. 

## How does it do it?

You decide which users are TaxonomyEngine content reviewers, and you define your taxonomy. The reviewer will have the opportunity to apply tags at the end of each article. The article isn't immediately tagged - you decide for each reviewer how much you trust them, and when they tag an article it gets a score based on that trust. Once it passes a threshold, we accept the tags as accurate. 

This means that you could require two interns to agree on a tag to accept an article, while a senior editor's tags could immediately accepted. If you crowdsource from your readers, perhaps you need ten of them to agree. Or five readers and one intern would approve the tagging.

As the system learns your article tagging based on your specific content, it will start suggesting tags. We track the accuracy of the machine learning predictions, and once it passes a defined point, we can autotag the historic corpus.

## Installing from Source

### Installing dependencies

```
npm install
npm run build
composer update --ignore-platform-reqs
```