# Gateway Clients
**An interface for build rest api client (1-n).**

## Requirements

* [PHP >= 7.0.0](http://php.net)


## Installation

The preferred way to install this is through [composer](http://getcomposer.org/download/).

```sh
composer require "vxm/gateway-clients"
```

or add

```json
"vxm/gateway-clients": "*"
```

to the require section of your composer.json.


## Interfaces Introduce

|Interface | Details| 
|------|--------|
|[**GatewayInterface**](src/GatewayInterface.php)|It should be implemented by classes provide gateway server api information.
|[**ClientInterface**](src/ClientInterface.php)|It should be implemented by classes provide information for access to gateway server api via [**GatewayInterface**](src/GatewayInterface.php).
|[**DataInterface**](src/DataInterface.php)|It should be implemented by classes provide data for support [**GatewayInterface**](src/GatewayInterface.php) make request or get response data from gateway server api.


## Abstraction layer 

* [For Yii2 framework](https://github.com/vuongxuongminh/yii2-gateway-clients)

