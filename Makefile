
# SPDX-FileCopyrightText: Bernhard Posselt <dev@bernhard-posselt.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

# Generic Makefile for building and packaging a Nextcloud app which uses npm and
# Composer.
#
# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * npm: for building and testing everything JS
#
# If no composer.json is in the app root directory, the Composer step
# will be skipped. The same goes for the package.json which can be located in
# the app root or the js/ directory.
#
# The npm command by launches the npm build script:
#
#    npm run build
#
# The npm test command launches the npm test script:
#
#    npm run test
#
# The idea behind this is to be completely testing and build tool agnostic. All
# build tools and additional package managers should be installed locally in
# your project, since this won't pollute people's global namespace.
#
# The following npm scripts in your package.json install and update the bower
# and npm dependencies and use gulp as build system (notice how everything is
# run from the node_modules folder):
#
#    "scripts": {
#        "test": "node node_modules/gulp-cli/bin/gulp.js karma",
#        "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
#        "build": "node node_modules/gulp-cli/bin/gulp.js"
#    },
app_name=integration_oidc
get_version = $(shell  grep /version $(app_name)/appinfo/info.xml | sed 's/.*\([0-9]\.[0-9]\.[0-9]\).*/\1/')
cert_dir=$(HOME)/.nextcloud/certificates
project_dir=$(CURDIR)/$(app_name)
build_dir=$(project_dir)/build/artifacts
build_tools_dir=$(project_dir)/build/tools
sign_dir=$(build_dir)/sign
version := $(call get_version)

all: appstore
release: appstore
.PHONY: selfsignedcert
selfsignedcert:
	openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj "/C=US/ST=Denial/L=Springfield/O=Dis/CN=localhost"  -keyout /tmp/localhost.key  -out /tmp/localhost.crt
	cat /tmp/localhost.key /tmp/localhost.crt > /tmp/localhost.pem

.PHONY: docker_kill
docker_kill:
	docker kill nextcloud 2&> /dev/null || true
.PHONY: docker
docker: selfsignedcert docker_kill package
	$(shell echo 'LoadModule socache_shmcb_module /usr/lib/apache2/modules/mod_socache_shmcb.so \nLoadModule ssl_module /usr/lib/apache2/modules/mod_ssl.so \nSSLRandomSeed startup builtin \nSSLRandomSeed startup file:/dev/urandom 512 \nSSLRandomSeed connect builtin \nSSLRandomSeed connect file:/dev/urandom 512 \nAddType application/x-x509-ca-cert .crt \nAddType application/x-pkcs7-crl .crl \nSSLPassPhraseDialog  exec:/usr/share/apache2/ask-for-passphrase \nSSLSessionCache     shmcb:$${APACHE_RUN_DIR}/ssl_scache(512000) \nSSLSessionCacheTimeout  300 \nSSLCipherSuite HIGH:!aNULL \nSSLProtocol all -SSLv3 \nSSLSessionTickets off' > /tmp/nextcloud-ssl.conf )
	$(shell echo 'Listen 8443 \n<VirtualHost *:8443> \nServerAdmin webmaster@localhost \nDocumentRoot /var/www/html \nSSLEngine on \nSSLCertificateFile /etc/ssl/private/localhost.pem \nSSLCertificateKeyFile /etc/ssl/private/localhost.pem \nHeader always set Strict-Transport-Security "max-age=0" \nErrorLog $${APACHE_LOG_DIR}/sslerror.log \nCustomLog $${APACHE_LOG_DIR}/sslaccess.log combined \n</VirtualHost>' > /tmp/nextcloud-8443.conf)
	docker run --rm --detach --expose 8443 -p 8443:8443 \
		--mount type=bind,source=/tmp/nextcloud-ssl.conf,target=/etc/apache2/mods-enabled/nextcloud.conf \
		--mount type=bind,source=/tmp/nextcloud-8443.conf,target=/etc/apache2/sites-enabled/nextcloud.conf \
		--mount type=bind,source=/tmp/localhost.pem,target=/etc/ssl/private/localhost.pem \
		--name nextcloud nextcloud:latest
	sleep 10
	docker cp $(build_dir)/$(app_name)-$(version).tar.gz nextcloud:/var/www/html/custom_apps
	docker exec -u www-data nextcloud /bin/bash -c "cd /var/www/html/custom_apps && tar -xzf $(app_name)-$(version).tar.gz && rm $(app_name)-$(version).tar.gz"
	docker exec nextcloud /bin/bash -c "chown -R www-data:www-data /var/www/html/custom_apps/$(app_name)"
	docker exec -u www-data nextcloud /bin/bash -c "/var/www/html/occ maintenance:install --admin-user='admin' --admin-pass='adminpassword'"
	docker exec -u www-data nextcloud /bin/bash -c "env OC_PASS=nomfauserpassword /var/www/html/occ user:add --password-from-env --display-name='Ordinary User' nomfauser"
	firefox -new-tab https://localhost:8443/

sign: package docker_kill
	docker run --rm --volume $(cert_dir):/certificates --detach --name nextcloud nextcloud:latest
	sleep 5
	docker exec -u www-data nextcloud /bin/bash -c "mkdir -p /var/www/html/custom_apps"
	docker cp $(build_dir)/$(app_name)-$(version).tar.gz nextcloud:/var/www/html/custom_apps
	docker exec -u www-data nextcloud /bin/bash -c "cd /var/www/html/custom_apps && tar -xzf $(app_name)-$(version).tar.gz && rm $(app_name)-$(version).tar.gz"
	docker exec -u www-data nextcloud /bin/bash -c "php /var/www/html/occ integrity:sign-app --certificate /certificates/$(app_name).crt --privateKey /certificates/$(app_name).key --path /var/www/html/custom_apps/$(app_name)"
	docker exec -u www-data nextcloud /bin/bash -c "cd /var/www/html/custom_apps && tar pzcf $(app_name)-$(version).tar.gz $(app_name)"
	docker cp nextcloud:/var/www/html/custom_apps/$(app_name)-$(version).tar.gz $(build_dir)/$(app_name)-$(version).tar.gz
	sleep 3
	docker kill nextcloud

appstore: sign

clean:
	rm -rf $(build_dir)

package: clean build
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/translationfiles \
	--exclude=.tx \
	--exclude=/tests \
	--exclude=.git \
	--exclude=.github \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=.gitattributes \
	--exclude=.gitignore \
	--exclude=.scrutinizer.yml \
	--exclude=.travis.yml \
	--exclude=/Makefile \
	--exclude=.drone.yml \
	$(project_dir)/ $(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name)-$(version).tar.gz \
		-C $(sign_dir) $(app_name)


# Fetches the PHP and JS dependencies and compiles the JS. If no composer.json
# is present, the composer step is skipped, if no package.json or js/package.json
# is present, the npm step is skipped
.PHONY: build
build:
ifneq (,$(wildcard $(project_dir)/composer.json))
	make composer
endif
ifneq (,$(wildcard $(project_dir)/package.json))
	make npm
endif
ifneq (,$(wildcard $(project_dir)/js/package.json))
	make npm
endif

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_dir)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_dir)
	cd $(project_dir) && php $(build_tools_dir)/composer.phar install --prefer-dist
else
	cd $(project_dir) && composer install --prefer-dist
endif

# Installs npm dependencies
.PHONY: npm
npm:
	cd $(project_dir) && npm install
	cd $(project_dir) && npm run build

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf $(project_dir)/vendor
	rm -rf $(project_dir)/node_modules

.PHONY: test
test: composer
	$(project_dir)/vendor/phpunit/phpunit/phpunit -c $(project_dir)/phpunit.xml
	$(project_dir)/vendor/phpunit/phpunit/phpunit -c $(project_dir)/phpunit.integration.xml
