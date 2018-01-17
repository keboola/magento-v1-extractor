# Magento v1 Extractor

Docker Wrapper for [keboola/magento-extractor-bundle](https://github.com/keboola/magento-extractor-bundle)

## Status

[![Build Status](https://travis-ci.org/keboola/magento-v1-extractor.svg)](https://travis-ci.org/keboola/magento-v1-extractor)

## Configuration

```
{
  "parameters": {
    "apiUrl": "http://magento_instance",
    "oauthConsumerKey": "",
    "#oauthConsumerSecret": "",
    "oauthToken": "",
    "#oauthTokenSecret": "",
    "jobs": {
      "1": {
        "endpoint": "products",
        "params": "{\"limit\":100}",
        "dataType": "",
        "dataField": "",
        "rowId": "products"
      }
    }
  }
}
```

The `jobs` section is a simple json form of a csv configuration table from the old extractor, see [keboola/magento-extractor-bundle](https://github.com/keboola/magento-extractor-bundle#data) for details.