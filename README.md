OpenSpaceLint
=============

OpenSpaceLint is a validator for the Space API. It also be used it to reformat any JSON.

Configuration
-------------

Copy config.sample.php to config.php and fill in the correct api keys.

Proxy
-----

If OpenSpaceLint is deployed on a shared host or on a machine where curl is not allowed to use non-standard ports an external proxy is used (available at [jasonproxy.herokuapp.com](http://jasonproxy.herokuapp.com)) to bypass the firewall.