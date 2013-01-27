OpenSpaceLint
=============

OpenSpaceLint is a validator for the Space API. It also be used it to reformat any JSON.

Checkout
---------

In your ```DocumentRoot``` get OpenSpaceLint via git

```
git clone git://github.com/slopjong/OpenSpaceLint.git
mv OpenSpaceLint/* .
mv OpenSpaceLint/.* .
rm -rf OpenSpaceLint
```

or if git isn't installed via wget

```
wget https://github.com/slopjong/OpenSpaceLint/archive/master.zip
unzip master.zip
mv OpenSpaceLint-master/* .
mv OpenSpaceLint-master/.* .
rm -rf master.zip OpenSpaceLint-master
```

If you were root when executing the above commands you may need to change the file permissions, owner and group.

```
chown -R www-data:www-data .
find . -type d -exec chmod g+s {} \;
```

Configuration
-------------

Copy config.sample.php to config.php and fill in the correct api keys.

By default the ```site_url``` variable is set to the hostname or if virtual hosts are used then it's set to its ```ServerName```. Here is an example how the VirtualHost block looks like:

```
<VirtualHost *:80>
    ...
    ServerName openspace.slopjong.de
    ServerAlias www.openspace.slopjong.de
    ...
</VirtualHost>
```

If you cannot become root where OpenSpaceLint is being deployed then change ```site_url``` to the domain where it will be accessible.

One place where the ```site_url``` is used is the cache update script which only allows requests from the server itself and redirects all other clients to *site_url's* error page.

Cache Cron Setup
----------------

In the setup directory execute the setup script. Replace ```www-data``` with the user under which your web server is running.

```
cd setup
./setup www-data
```

Then run

```
su www-data
crontab -e
```

to add the actual cronjobs. Replace ```www-data``` with the user under which your web server is running. In your linux distribution this could be ```http```.

Now copy the following lines by replacing <docroot> with the proper ```DocumentRoot``` from your VirtualHost configuration.

```
*/2   *     *    *    *    run-parts <docroot>/cron/cron.min.02
*/5   *     *    *    *    run-parts <docroot>/cron/cron.min.05
*/10  *     *    *    *    run-parts <docroot>/cron/cron.min.10
*/15  *     *    *    *    run-parts <docroot>/cron/cron.min.15
*/30  *     *    *    *    run-parts <docroot>/cron/cron.min.30
*     */1   *    *    *    run-parts <docroot>/cron/cron.h.01
*     */2   *    *    *    run-parts <docroot>/cron/cron.h.02
*     */4   *    *    *    run-parts <docroot>/cron/cron.h.04
*     */8   *    *    *    run-parts <docroot>/cron/cron.h.08
*     */12  *    *    *    run-parts <docroot>/cron/cron.h.12
*     *     */1  *    *    run-parts <docroot>/cron/cron.d.01
```

Proxy
-----

If OpenSpaceLint is deployed on a shared host or on a machine where curl is not allowed to use non-standard ports an external proxy is used (available at [jasonproxy.herokuapp.com](http://jasonproxy.herokuapp.com)) to bypass the firewall. The source code of the proxy is available in the [JSONProxy](https://github.com/slopjong/JSONProxy) repository.
