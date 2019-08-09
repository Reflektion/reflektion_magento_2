# Magento 2:  Reflektion Magento Extension

## 1. Description

Reflektion is the leader in individualized personalization solutions for the retail industry. Founded by Google pioneers in 2012 and named a Shop.org Digital Commerce Startup of the Year, Reflektion is an AI-driven customer engagement platform that understands and influences the intent of each customer in real-time and instantly delivers the most individually relevant content across the touchpoints that matter most—including Web, site search, merchandising, and email. Magento merchants have turn key access to Reflektion’s powerful and personalized site search and category page solutions. Both of which re-rank search and category page products based on each customer’s behaviors on the site, have Natural Language Processing built in, and can be deployed seamlessly into Magento 2 storefronts with little to no developer resources needed.

Note: A Reflektion account is required to use this extension. To inquire about opening an account and pricing details, visit our contact page.
  

## FEATURES
### Preview Search

Transforms your on-site search function into a visual search experience that previews individually relevant products as customers type into the search box—so they are one click away from the items they are most likely to buy.

### Full Page Search

Delivers the most individually relevant, personalized search results page imaginable by pairing your brand’s product offerings with an in-depth understanding of each individual customer.  Reflektion dynamically re-ranks the search results to ensure they are relevant to the individual searcher’s preferences and intent.

### Category Pages

Ensure that you have the most individually relevant and SEO category pages on your site.  Reflektion’s ability to understand the preferences and intent of each individual shopper turns what are typically generic one size fits all category pages into a personalized result of products re-ranked to maximize relevance for each shopper.


## 2. How to install

### Install via composer (recommend)

- Run the following command in Magento 2 root folder:
```
composer config repositories.reflektion-reflektion_magento_2 git git@github.com:reflektion/reflektion_magento_2.git
composer require reflektion/reflektion_magento_2:dev-master
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
php bin/magento cache:clean
```

### Contributors

Reflektion Team

### User Guide

https://marketplace.magento.com/media/catalog/product/reflektion-catalogexport-1-0-1-ce/user_guides.pdf
