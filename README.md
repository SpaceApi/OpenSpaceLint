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

It's highly recommended to change explicitly the ```site_url``` variable to the domain where it will be accessible. By default it's set to a server variable but php code run from the command line the ```$_SERVER["SERVER_NAME"]``` is undefined which might break cron jobs relying on the php API.

Use an URL of the form openspace.slopjong.de and leave the protocol away.

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
*/2   *     *    *    *    run-parts <docroot>/cron/scron.m.02
*/5   *     *    *    *    run-parts <docroot>/cron/scron.m.05
*/10  *     *    *    *    run-parts <docroot>/cron/scron.m.10
*/15  *     *    *    *    run-parts <docroot>/cron/scron.m.15
*/30  *     *    *    *    run-parts <docroot>/cron/scron.m.30
*     */1   *    *    *    run-parts <docroot>/cron/scron.h.01
*     */2   *    *    *    run-parts <docroot>/cron/scron.h.02
*     */4   *    *    *    run-parts <docroot>/cron/scron.h.04
*     */8   *    *    *    run-parts <docroot>/cron/scron.h.08
*     */12  *    *    *    run-parts <docroot>/cron/scron.h.12
*     *     */1  *    *    run-parts <docroot>/cron/scron.d.01
*     *     */1  *    *    run-parts <docroot>/cron/daily-tasks
```

Every cron directory starting with scron only contains so-called space crons which only update the space JSON files. The daily-tasks directory contains system tasks such as recreating the key tables which list what of the space api a space has implemented or even new introduced fields. These tables are displayed on [openspace.slopjong.de](http://openspace.slopjong.de), just click on directory below the editor to see them.

A space cron is located in all the space cron directories but only in one directory it is executable. If a space changes the schedule the execution bit will be removed in the old schedule directory and added in the new one. That's how scheduling works in OpenSpaceLint.

Recaptcha
---------

Don't forget to enable the domain in your [recaptcha](http://recaptcha.com/) account.

Proxy
-----

If OpenSpaceLint is deployed on a shared host or on a machine where curl is not allowed to use non-standard ports an external proxy is used (available at [jasonproxy.herokuapp.com](http://jasonproxy.herokuapp.com)) to bypass the firewall. The source code of the proxy is available in the [JSONProxy](https://github.com/slopjong/JSONProxy) repository.


Troubleshooting
---------------

In cli you can check some filepaths with ```php -f c/php/controller.php delegator=environment```.

Submodules
----------

Example of how to add a submodule:

```
git submodule add git@github.com:SpaceApi/phpjs.git c/js/phpjs
```