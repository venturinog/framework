%define modname framework
Summary: Issabel is a Web based software to administrate a PBX based in open source programs, forked from Elastix
Name:    issabel-framework
Version: 6.0.0
Release: 64
License: GPL
Vendor: Issabel Foundation
Group: Applications/System
Source0: issabel-%{modname}-%{version}.tar.gz
#Patch0:  baserepo-40.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires(pre): /usr/sbin/chkconfig, /etc/sudoers, sudo
Requires(pre): php, php-gd, php-xml, php-mysqlnd, php-pdo, php-soap
Requires(pre): httpd, mariadb-server, chrony, mod_ssl
Requires(pre): perl
Requires: issabel-firstboot >= 5.0.0-2

#Requires: issabelPBX
Requires(pre): /usr/sbin/pidof
Requires: liberation-sans-fonts
Obsoletes: elastix-additionals
Conflicts: elastix-system <= 4.0.0-8
Conflicts: elastix-callcenter <= 2.0.0-16
Conflicts: elastix-pbx <= 2.2.0-16
Conflicts: elastix-fax <= 2.2.0-5
Conflicts: elastix-email_admin <= 2.3.0-8
Conflicts: elastix-developer <= 2.3.0-4
Conflicts: elastix-addons <= 2.5.0-3
Conflicts: elastix-monitoring_services <= 5.4.1-8
Conflicts: kernel-module-dahdi
Conflicts: kernel-module-rhino
Conflicts: kernel-module-wanpipe
Conflicts: kernel-module-dahdi-xen
Conflicts: kernel-module-rhino-xen
Conflicts: kernel-module-wanpipe-xen
Obsoletes: elastix <= 2.2.0-17
Requires: php-smarty4
Requires: issabel-php-tcpdf
Requires: issabel-php-phpmailer
Obsoletes: elastix-framework
Provides: elastix-framework

# commands: uname df rm cat
Requires: coreutils

# commands: uptime
Requires: procps

# commands: rpm
Requires: rpm

# commands: /usr/bin/mysql /usr/bin/mysqldump
Requires: mariadb

# commands: /usr/bin/sqlite3
Requires: sqlite

# FIXME: /usr/local/issabel/sampler.php requieres /usr/sbin/asterisk but
# issabel-framework should stand by itself without an asterisk dependency.

%description
Issabel is a Web based software to administrate a PBX based in open source programs

%package themes-extra
Summary: Issabel GUI themes from 2.4 and earlier
Group: Applications/System
BuildArch: noarch
Requires: issabel-framework = %{version}-%{release}

%description themes-extra
This package provides the Issabel GUI themes from earlier versions.

%prep
%setup -n %{name}-%{version}
#%patch0 -p1

%install
## ** Step 1: Creation path for the installation ** ##
rm -rf   $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

# ** /var path ** #
mkdir -p $RPM_BUILD_ROOT/var/www/db
mkdir -p $RPM_BUILD_ROOT/var/www/html
mkdir -p $RPM_BUILD_ROOT/var/www/backup
mkdir -p $RPM_BUILD_ROOT/var/lib/php/session-asterisk
mkdir -p $RPM_BUILD_ROOT/var/lib/php/session-asterisk/tabs

# ** /usr path ** #
mkdir -p $RPM_BUILD_ROOT/usr/local/bin
mkdir -p $RPM_BUILD_ROOT/usr/local/issabel
mkdir -p $RPM_BUILD_ROOT/usr/local/sbin
mkdir -p $RPM_BUILD_ROOT/usr/sbin
mkdir -p $RPM_BUILD_ROOT/usr/bin
mkdir -p $RPM_BUILD_ROOT/usr/share/issabel
mkdir -p $RPM_BUILD_ROOT/usr/share/pear/DB
mkdir -p $RPM_BUILD_ROOT/usr/share/issabel/privileged
mkdir -p $RPM_BUILD_ROOT/usr/share/issabel/module_installer/%{name}-%{version}-%{release}/
mkdir -p $RPM_BUILD_ROOT/usr/lib/tmpfiles.d

# ** /etc path ** #
mkdir -p $RPM_BUILD_ROOT/etc/cron.d
mkdir -p $RPM_BUILD_ROOT/etc/httpd/conf.d
mkdir -p $RPM_BUILD_ROOT/etc/php.d
mkdir -p $RPM_BUILD_ROOT/etc/profile.d
mkdir -p $RPM_BUILD_ROOT/etc/yum.repos.d
mkdir -p $RPM_BUILD_ROOT/etc/init.d


## ** Step 2: Installation of files and folders ** ##
# ** Installating framework issabel webinterface ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/framework/html/*                              $RPM_BUILD_ROOT/var/www/html/

# ** Installating modules issabel webinterface ** #

chmod 777 $RPM_BUILD_ROOT/var/www/db/
chmod 755 $RPM_BUILD_ROOT/usr/share/issabel/privileged

# ** Httpd and Php config ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/httpd/conf.d/issabel.conf        $RPM_BUILD_ROOT/etc/httpd/conf.d/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/httpd/conf.d/issabel-htaccess.conf  $RPM_BUILD_ROOT/etc/httpd/conf.d/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/php.d/issabel.ini                $RPM_BUILD_ROOT/etc/php.d/

# ** crons config ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/cron.d/issabel.cron              $RPM_BUILD_ROOT/etc/cron.d/
chmod 644 $RPM_BUILD_ROOT/etc/cron.d/*

# ** Runtime tab-store directory cleanup ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/lib/tmpfiles.d/issabel-tabs.conf $RPM_BUILD_ROOT/usr/lib/tmpfiles.d/
chmod 644 $RPM_BUILD_ROOT/usr/lib/tmpfiles.d/issabel-tabs.conf

# ** Repos config ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/yum.repos.d/Issabel.repo         $RPM_BUILD_ROOT/etc/yum.repos.d/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/yum.repos.d/Issabel5.repo        $RPM_BUILD_ROOT/etc/yum.repos.d/

# ** sudoers config ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/sudoers                          $RPM_BUILD_ROOT/usr/share/issabel/

# ** profile login server info ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/profile.d/login-info.sh          $RPM_BUILD_ROOT/etc/profile.d/

# ** /usr/local/ files ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/local/issabel/sampler.php        $RPM_BUILD_ROOT/usr/local/issabel/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/local/sbin/motd.sh               $RPM_BUILD_ROOT/usr/local/sbin/
chmod 755 $RPM_BUILD_ROOT/usr/local/sbin/motd.sh

# ** /usr/share/ files ** #
# mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/share/pear/DB/sqlite3.php                    $RPM_BUILD_ROOT/usr/share/pear/DB/

# ** setup ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/framework/setup/usr/share/issabel/privileged/*   $RPM_BUILD_ROOT/usr/share/issabel/privileged/
rmdir framework/setup/usr/share/issabel/privileged/ framework/setup/usr/share/issabel
rmdir framework/setup/usr/share framework/setup/usr
mv $RPM_BUILD_DIR/%{name}-%{version}/framework/setup/                                 $RPM_BUILD_ROOT/usr/share/issabel/module_installer/%{name}-%{version}-%{release}/
mv $RPM_BUILD_DIR/%{name}-%{version}/framework/menu.xml                               $RPM_BUILD_ROOT/usr/share/issabel/module_installer/%{name}-%{version}-%{release}/

# ** issabel -* file ** #
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/issabel-menumerge            $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/issabel-menuremove           $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/issabel-dbprocess            $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/compareVersion		   $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/search_ami_admin_pwd             $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/issabel-add-yum-exclude             $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/issabel-notification             $RPM_BUILD_ROOT/usr/bin/
chmod 755 $RPM_BUILD_ROOT/usr/bin/compareVersion
chmod 755 $RPM_BUILD_ROOT/usr/bin/search_ami_admin_pwd

# ** Moving issabel_helper
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/bin/issabel-helper               $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/sbin/issabel-helper              $RPM_BUILD_ROOT/usr/sbin/

chmod 755 $RPM_BUILD_ROOT/usr/sbin/issabel-helper
chmod 755 $RPM_BUILD_ROOT/usr/bin/issabel-helper


# Archivos generic-cloexec y close-on-exec.pl
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/usr/sbin/close-on-exec.pl            $RPM_BUILD_ROOT/usr/sbin/
mv $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/init.d/generic-cloexec           $RPM_BUILD_ROOT/etc/init.d/

#Logrotate
mkdir -p    $RPM_BUILD_ROOT/etc/logrotate.d/
mv          $RPM_BUILD_DIR/%{name}-%{version}/additionals/etc/logrotate.d/*           $RPM_BUILD_ROOT/etc/logrotate.d/
# Los archivos de logrotate TIENEN que ser 0644 (http://bugs.elastix.org/view.php?id=2608)
chmod 644 $RPM_BUILD_ROOT/etc/logrotate.d/*

# File Issabel Access Audit log
mkdir -p    $RPM_BUILD_ROOT/var/log/issabel
touch       $RPM_BUILD_ROOT/var/log/issabel/audit.log

%pre
#Para conocer la version de issabel antes de actualizar o instalar
mkdir -p /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/preversion_framework.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%%{VERSION}-%%{RELEASE}' %{name} > /usr/share/issabel/module_installer/%{name}-%{version}-%{release}/preversion_framework.info
fi

# if not exist add the asterisk group
grep -c "^asterisk:" %{_sysconfdir}/group &> /dev/null
if [ $? = 1 ]; then
    echo "   0:adding group asterisk..."
    /usr/sbin/groupadd -r -f asterisk
else
    echo "   0:group asterisk already present"
fi

# At this point the asterisk group must already exist
if ! grep -q asterisk: /etc/passwd ; then
    echo -e "Adding new user asterisk..."
    /usr/sbin/useradd -r -g asterisk -c "Asterisk PBX" -s /usr/bin/bash -d %{_localstatedir}/lib/asterisk asterisk
fi

# No modificamos nada, el usuario lo crean los paquetes asterisk
# /usr/sbin/usermod -c "Asterisk VoIP PBX" -g asterisk -s /usr/bin/bash -d /var/lib/asterisk asterisk

# TODO: TAREA DE POST-INSTALACIÓN
#useradd -d /var/ftp -M -s /usr/sbin/nologin ftpuser
#(echo asterisk2007; sleep 2; echo asterisk2007) | passwd ftpuser

%post

# TODO: tarea de post-instalación.
# Habilito inicio automático de servicios necesarios
#chkconfig --level 345 ntpd on
#chkconfig --level 345 mysqld on
#chkconfig --level 345 mariadb on
#chkconfig --level 345 httpd on
#chkconfig --del cups  &> /dev/null
#chkconfig --del gpm   &> /dev/null
systemctl enable mariadb || :
systemctl enable httpd || :
systemd-tmpfiles --create /usr/lib/tmpfiles.d/issabel-tabs.conf >/dev/null 2>&1 || :

mkdir -p /usr/share/fonts/truetype

# ** Change content of sudoers ** #
cat   /usr/share/issabel/sudoers > /etc/sudoers.d/issabel
rm -f /usr/share/issabel/sudoers
rm -f /etc/yum.repos.d/elastix.repo

# ** Change content of CentOS-Base.repo ** #
if [ -e /etc/yum.repos.d/CentOS-Base.repo ] ; then
    /usr/bin/issabel-add-yum-exclude /etc/yum.repos.d/CentOS-Base.repo 'redhat-logos' 'php53*' 'kernel*'
fi

# Patch httpd.conf so that User and Group directives in issabel.conf take effect
sed --in-place "s,User\sapache,#User apache,g" /etc/httpd/conf/httpd.conf
sed --in-place "s,Group\sapache,#Group apache,g" /etc/httpd/conf/httpd.conf

# Patch php.conf to remove the assignment to session.save_path in CentOS 7
sed --in-place "s,php_value session.save_path,#php_value session.save_path,g" /etc/httpd/conf.d/php.conf

# ** Uso de issabel-dbprocess ** #
pathModule="/usr/share/issabel/module_installer/%{name}-%{version}-%{release}"
preversion=`cat $pathModule/preversion_framework.info`
rm -f $pathModule/preversion_framework.info

# Set random pbxapi key
RANDKEY=%(date +%s | sha256sum | base64 | head -c 64 ; echo)
sed --in-place "s,da893kasdfam43k29akdkfaFFlsdfhj23rasdf,$RANDKEY,g" /var/www/html/pbxapi/index.php

if [ $1 -eq 1 ]; then #install
    # The installer database
    issabelversion=`rpm -q --queryformat='%%{VERSION}-%%{RELEASE}' issabel`
    verifyVersion=`echo $issabelversion | grep -oE "^[0-9]+(\.[0-9]+){1,2}-[0-9]+$"`
    if [ "$verifyVersion" == "" ]; then
	issabel-dbprocess "install" "$pathModule/setup/db"
    else
	issabel-dbprocess "update"  "$pathModule/setup/db" "$verifyVersion"
    fi
    /usr/sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
    	echo "Restarting apache..."
    	/usr/sbin/service httpd restart > /dev/null 2>&1
    fi
elif [ $1 -eq 2 ]; then #update
    issabel-dbprocess "update"  "$pathModule/setup/db" "$preversion"
    /usr/sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
    	# Para versiones menores a 2.2.0-15 se debe reiniciar el apache debido a cambios en issabel.conf
    	compareVersion "$preversion" "2.2.0-15"
    	if [ "$?" == "9" ]; then
        	echo "Restarting apache..."
        	/usr/sbin/service httpd restart > /dev/null 2>&1
    	fi
    fi
fi

chown -R asterisk.asterisk /var/www/html 

# Se revisa la clave de ami si esta en /etc/issabel.conf
search_ami_admin_pwd
if [ "$?" == "1" ]; then
	echo "Restarting amportal..."
        /usr/sbin/amportal restart > /dev/null 2>&1
fi

# Actualizacion About Version Release
# Verificar si en la base ya existe algo
if [ "`sqlite3 /var/www/db/settings.db "select count(key) from settings where key='issabel_version_release';"`" = "0" ]; then
    `sqlite3 /var/www/db/settings.db "insert into settings (key, value) values('issabel_version_release','%{version}-%{release}');"`
else
    #Actualizar
    `sqlite3 /var/www/db/settings.db "update settings set value='%{version}-%{release}' where key='issabel_version_release';"`
fi

# Para q se actualice smarty (tpl updates)
rm -rf /var/www/html/var/templates_c/*

# Pick up PHP code/php.d changes from this update without a manual restart.
systemctl reload php-fpm &>/dev/null || :

# Patch issabel.ini to work around %config(noreplace) in previous versions
sed --in-place "s,/tmp,/var/lib/php/session-asterisk,g" /etc/php.d/issabel.ini
if [ $1 -eq 1 ]; then #install
    /usr/sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
        echo "Restarting apache..."
        /usr/sbin/service httpd restart > /dev/null 2>&1
    fi
elif [ $1 -eq 2 ]; then #update
    /usr/sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
        # Para versiones menores a 2.4.0-11 se debe reiniciar el apache debido a cambios en issabel.ini
        # respecto a los archivos de sessiones, por ello tambien hay que reubicarlos
        compareVersion "$preversion" "2.4.0-11"
        if [ "$?" == "9" ]; then
             # Patch issabel.ini, relocate session files to the new path.
            echo "Session files in the old directory. Starting relocation process..."
            for file_sess in `ls /tmp/sess_*`
            do
              file_name=`basename $file_sess`
              if [ -f /var/lib/php/session-asterisk/$file_name ]; then
                rm -rf /var/lib/php/session-asterisk/$file_name
              fi

              echo "Copying file /tmp/$file_name to /var/lib/php/session-asterisk/$file_name."
              cp -p /tmp/$file_name /var/lib/php/session-asterisk/
            done

            echo "Restarting apache..."
            /usr/sbin/service httpd restart > /dev/null 2>&1
        fi
    fi
fi

# Merge current menu.xml for userlist custom privileges
issabel-menumerge $pathModule/menu.xml

# Los archivos de logrotate TIENEN que ser 0644 (http://bugs.elastix.org/view.php?id=2608)
chmod 644 /etc/logrotate.d/issabelAudit.logrotate

if [ "$1" -ge 1 ]; then
    /usr/bin/issabel-admin-passwords --ensure-jwt-secret >/dev/null 2>&1 || :
fi

%preun
# Reverse the patching of php.conf
sed --in-place "s,#php_value session.save_path,php_value session.save_path,g" /etc/httpd/conf.d/php.conf

# Reverse the patching of httpd.conf
sed --in-place "s,#User\sapache,User apache,g" /etc/httpd/conf/httpd.conf
sed --in-place "s,#Group\sapache,Group apache,g" /etc/httpd/conf/httpd.conf
pathModule="/usr/share/issabel/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Dump and delete %{name} databases"
  issabel-dbprocess "delete" "$pathModule/setup/db"
fi

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/var/www/db
/var/www/backup
/var/log/issabel/audit.log
# %config(noreplace) /var/www/db/
%defattr(-, root, root)
/var/www/html/configs
/var/www/html/favicon.ico
/var/www/html/help
/var/www/html/images
/var/www/html/lang
/var/www/html/libs
/var/www/html/modules
/var/www/html/themes/tenant
/var/www/html/themes/modern
/var/www/html/*.php
/var/www/html/robots.txt
/var/www/html/panels/README.en
/var/www/html/panels/README.es
/var/www/html/panels/README.uk
/var/www/html/var
/var/www/html/pbxapi
/var/www/html/var/.htaccess
/usr/share/issabel/*
#/usr/share/pear/DB/sqlite3.php
/usr/local/issabel/sampler.php
/usr/local/sbin/motd.sh
/usr/sbin/close-on-exec.pl
/usr/bin/issabel-menumerge
/usr/bin/issabel-menuremove
/usr/bin/issabel-dbprocess
/usr/bin/issabel-helper
/usr/bin/issabel-add-yum-exclude
/usr/bin/issabel-notification
/usr/bin/compareVersion
/usr/bin/search_ami_admin_pwd
/usr/sbin/issabel-helper
%config(noreplace) /etc/profile.d/login-info.sh
%config(noreplace) /etc/cron.d/issabel.cron
%config(noreplace) /etc/httpd/conf.d/issabel.conf
%config(noreplace) /etc/php.d/issabel.ini
/etc/yum.repos.d/Issabel.repo
/etc/yum.repos.d/Issabel5.repo
#%config(noreplace) /etc/yum.repos.d/Issabel.repo
%config(noreplace) /etc/logrotate.d/issabelAudit.logrotate
%config /etc/httpd/conf.d/issabel-htaccess.conf
/usr/lib/tmpfiles.d/issabel-tabs.conf
/etc/init.d/generic-cloexec
%defattr(755, root, root)
/usr/share/issabel/privileged/*
%defattr(770, root, asterisk, 770)
%dir /var/lib/php/session-asterisk
%attr(0750, asterisk, asterisk) %dir /var/lib/php/session-asterisk/tabs
%defattr(-, asterisk, asterisk)
/var/www/html/cache/.dummy
/var/www/html/templates_c/.dummy

%files themes-extra
%defattr(-, root, root)
/var/www/html/themes/*
%exclude /var/www/html/themes/tenant
%exclude /var/www/html/themes/modern

%changelog
* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-64
- Language: add the "%s is required" and "%s is invalid" keys to the global
  language files. Six modules carried their own copy of the two keys, so a
  module that validated a field had to add them again to be able to say which
  field was wrong. The global file is the single source now.

* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-63
- Theme: add the alert primitives to the Modern theme. The family is
  .iss-alert plus the .iss-alert--error, --success, --warning and --info
  modifiers, with __title, __content, __list and __dismiss helpers. Each
  variant has its own background, border and foreground tokens, so a tinted
  alert keeps its contrast in light and in dark mode; the old .iss-alert-error
  kept a hardcoded light background and turned into light text on a light
  background when the theme switched to dark.
- Theme: define .iss-alert-error__title and .iss-alert-error__content, which
  the network parameters module already used but the stylesheet never had.
- Theme: add the severity tokens (--iss-sev-ok, --iss-sev-warning,
  --iss-sev-critical, --iss-sev-unknown) for score borders and status dots.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-62
- Menu: drop the "Hardware Detector" name from the supported languages. The
  module it referred to was removed from issabel-system 6.0.0-55.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-61
- Menu: translate the "Health Center" menu name in the languages the Health
  module ships (en, es, bg, br, fa, fr, ru), so the module registered by
  issabel-system shows its name translated in the sidebar.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-60
- Sampler: add hourly and daily aggregates (samples_rollup) so long-term trends
  (7/30 days) can be shown without keeping months of raw 5-minute samples.
  sampler.php now upserts the current hour/day bucket and prunes the aggregates
  (hourly ~90 days, daily ~2 years), while raw samples keep their ~26 h window.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-59
- Escape the rpm queryformat used to record the pre-version on upgrade.
  The VERSION and RELEASE macros were expanded at build time, so %pre always
  wrote the new version and issabel-dbprocess skipped every version_sql
  migration. They are now escaped so rpm expands them at run time.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-58
- Vendor Chart.js 4.5.1 in the modern theme (js/chart.umd.min.js) so any module
  can use one shared MIT charting library instead of bundling its own copy.
- Add the sibling assets needed for the reports/dashboard charts:
  chartjs-plugin-datalabels 2.2.0, moment 2.30.1 and chartjs-adapter-moment
  1.0.0, plus a small issabel-charts.js helper that applies the theme colours
  (light/dark) to the Chart.js defaults.
- Keep the upstream license texts under themes/modern/vendor-licenses/.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-57
- Group Permissions: make the expand/collapse toggles more prominent with an
  outlined eye-catching button and a larger chevron (fs-5), and align the
  resource and custom-privilege checkboxes with the row text by dropping the
  empty label and using align-middle.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-56
- Group Permissions: replace the jQuery UI expand/collapse and checkbox icons
  with the theme kit. Branch nodes now use a Tabler chevron (ti-chevron-down
  expanded, ti-chevron-right collapsed) on a btn-icon ghost button, and the
  resource and custom-privilege checkboxes use the native form-check-input, so
  the selected/unselected state comes from Tabler instead of a decorated
  button. This drops the last jQuery UI .button() usage in the module and adds
  aria-expanded/aria-label for accessibility.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-55
- Users, Groups and Group Permissions: align the pages with the module style
  guide. Per-field server validation mapped by field name (not label) with
  is-invalid/form-control preserved and invalid-feedback in the templates, plus
  client-side feedback wrapped in {literal} with Cancel exempt. Groups view mode
  now renders plain text (readonly grid) instead of disabled inputs, and both
  forms gained the iss-reveal entry animation. Group Permissions filter uses the
  theme card grid, and the missing _tr() strings were registered in en.lang/es.lang.
- userlist: fix listUsers() call missing arguments in the no-permission branch
  (fatal in PHP 8), and translate the hardcoded Spanish DB/ACL bootstrap errors.
- CSRF: declare the read-only GET navigations (new, edit, edit_userExtension,
  assigncustom) so they no longer pollute audit.log and do not break under
  enforce mode.
- userlist: make the Save and Cancel buttons of the user form return to the
  listing. Cancel was never handled and simply re-rendered the form, and the
  post-save redirect dropped the tabs iframe context (rawmode/iss_instance) so
  the listing was not restored in the tab. A return URL helper keeps that
  context, and a plugin failure without a message now surfaces a generic one.
- Users and Groups forms: switch the field feedback to live validation. Fields
  now turn green as soon as they are valid while typing, the stale is-invalid
  from a previous submit is cleared, and an empty field only turns red after it
  loses focus (or on submit). The password mismatch is highlighted live on the
  confirm field.
- Grid: drop the <tfoot> that repeated the column headers at the bottom of the
  table when a list had more than three rows; it showed up as a stray last row
  (for example, a fourth group). Removed in the modern, tenant and farsi_rtl
  list templates.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-54
- Registration: add the Tabler 'contract' icon to the register link in the modern
  theme user menu. base.js and the registration module rewrote the whole anchor
  with jQuery .text(), which dropped any icon inside it on every page load; the
  label is now wrapped in a span and updated through a new setRegisterLabel()
  helper that only replaces the text, falling back to .text() for the legacy
  themes (tenant/farsi_rtl), whose anchors have no span.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-53
- Registration: use a muted amber (#b8860b) instead of pure yellow (#FFFF00 /
  'yellow') for the 'Register Server' and 'Incomplete registration' labels in
  the user menu. base.js paints those labels with the color returned by
  isRegisteredInfo(), and pure yellow was unreadable on the light background of
  the modern theme's dropdown.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-52
- Modern theme: style the disabled state of the legacy .button control, so a
  button blocked while an action is in progress is clearly visible.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-51
- Modern theme: add the .iss-cursor blinking caret and the .iss-console--pending
  state so modules can show a waiting indicator inside a command console.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-50
- Modern theme: add the .iss-console primitive for command output (ping,
  traceroute, logs). It uses a fixed dark palette so the text stays readable in
  both light and dark modes, where Tabler's pre styling made it unreadable.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-49
- Modern theme: make the cloud registration popup that the legacy themes show
  after login work again. The theme never rendered the 'a.register_link' item in
  the user menu, so base.js had nothing to click, and it did not define the
  LblRegister global; base.js now falls back to the lblRegisterCm hidden input
  when the global is missing. The framework modal (.neo-modal-issabel-popup-*)
  also had no styles in this theme, so the popup came up with no backdrop, no
  panel and no visible close button, leaving the GUI unusable; it is now styled
  from modern-compat.css with the theme tokens, the close button draws a Tabler
  icon, and the dialog can also be dismissed by clicking the backdrop or pressing
  Escape.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-48
- close-on-exec.pl: stop building a shell command line. The previous release
  quoted every argument, which also quoted the leading LC_MESSAGES=C environment
  assignment and made the shell try to run a program named 'LC_MESSAGES=C'
  ('command not found'), breaking every helper call from the GUI. Export the
  variable with $ENV and exec the program directly, so no argument needs quoting
  at all.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-47
- close-on-exec.pl: quote every argument instead of only the ones containing a
  space. An argument with shell metacharacters but no spaces (such as the JSON
  passed to 'netconfig --apply --change' by Network Parameters) was mangled by
  the shell, which stripped the double quotes and brace-expanded the payload,
  so the helper always failed with 'Invalid JSON' and the GUI could not apply
  any staged change set.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-46
- Modern theme: add the .iss-formgrid--readonly modifier so read-only summaries
  render labels as a small uppercase grey micro-label, visually distinct from the
  value below it.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-45
- Modern theme: add the .iss-reveal primitive (staggered fade/slide-in for form
  sections, disabled under prefers-reduced-motion) and .iss-field__value for
  read-only field values.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-44
- Keep PHP code changes from RPM updates visible without a manual restart:
  force opcache.validate_timestamps=1 / revalidate_freq=0 in /etc/php.d/issabel.ini
  and reload php-fpm in %post (httpd restart alone does not refresh php-fpm).

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-43
- Point the pending network change notice to network_parameters instead of the
  parent network menu, so the link opens the module with the right breadcrumb.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-42
- Translate the pending network change notice and "Network Parameters" to all
  shipped languages.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-41
- paloSantoValidar: add "ipv6" (single address) and "ipv6/mask" (address/prefix)
  validation types, for the IPv6 fields in Network Parameters.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-40
- Show a global notice (all themes) when a network change is pending
  confirmation, read from /var/lib/issabel/netconfig-rollback/status.json, so the
  admin sees it right after logging in on any page (dashboard included).
- CSRF: treat the network module read-only navigation actions (editInterfase,
  route_new, route_edit) as non-mutating to avoid false positives in audit.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-39
- Add a global CSRF helper (libs/issabelCsrf.class.php) with a single token per
  session, shared with the tab manager. paloForm and paloSantoGrid inject the
  hidden field automatically, jQuery AJAX sends it as an X-Issabel-CSRF-Token
  header, and index.php validates state-changing requests before dispatching a
  module. It starts in audit mode (logs to /var/log/issabel/audit.log) and is
  configured with $arrConf['csrf_mode'] and $arrConf['csrf_exempt_modules'].

* Wed Oct 14 2026 Issabel Foundation <info@issabel.org> - 6.0.0-38
- Modern theme: make the sidebar module search filter item by item (matching the
  label and the module id, accent- and separator-insensitive), auto-expanding the
  groups/subgroups that contain hits and highlighting the match, instead of
  showing whole top-level groups. Hide the quick links and the recent-modules
  list while searching and show a "No modules found" notice when nothing matches.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-35
- Modern form kit: trim the horizontal padding of the .iss-ip-octets controls
  so the IPv4 octet boxes can actually show their three digits.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-34
- Rotate the level-2 submenu chevron via aria-expanded so an expanded
  parent points up like the level-1 toggle.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-33
- Add the iss-ip-octets helper to the Modern form kit so groups of IPv4 octet
  inputs render inline (aligned) rather than stacked by .form-control.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-32
- Move the tab strip into the Modern topbar to preserve module content height.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-31
- Localize tab messages and cache-bust the tab shell and iframe bridges.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-30
- Keep same-module actions inside their tab iframe after module navigation.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-29
- Preserve iframe mode and tab identity across module-relative redirects.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-28
- Allow eight in-app tabs and hide the add control at the limit.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-27
- Show recoverable tab errors as dismissible notices and add init retry.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-26
- Broadcast Modern theme changes to loaded module iframes.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-25
- Highlight selected second-level module links with the same magenta state.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-24
- Load the shared jQuery assets in the Modern shell without module HTML.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-23
- Restore localized per-tab titles, breadcrumbs and sidebar selection; show a
  toast when navigation focuses an already-open tab. Expose the active menu ID
  in module documents so dashboard applets route AJAX requests to their owner.

* Tue Oct 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-22
- Add the JSON-backed in-app tab registry and its aged-file cleanup rule.

* Mon Oct 12 2026 Issabel Foundation <info@issabel.org> - 6.0.0-21
- Rotate the collapse trigger chevron via aria-expanded and style the jQuery
  UI datepicker image trigger as a proper field button in the compat layer.

* Mon Oct 12 2026 Issabel Foundation <info@issabel.org> - 6.0.0-20
- Make the "Show Filter" button toggle the filter row again: the labeled
  button never had a click handler in the modern theme (only the small
  chevron did), so every module's grid filter looked dead. Bind the click
  to both controls with event delegation and sync the label text and
  chevron icon with the row state.

* Mon Oct 12 2026 Issabel Foundation <info@issabel.org> - 6.0.0-18
- checkbox() now renders a Bootstrap 5 / Tabler switch (form-check form-switch)
  instead of the old checkbox markup, so the on/off toggles look consistent with
  the rest of the GUI and work in dark mode.

* Sun Oct 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-17
- Modern theme: theme the legacy third-party widgets used by the native modules
  (jQuery UI datepicker/dialog, Chosen, DataTables, FullCalendar, facebox,
  filestyle) and map the BS3 glyphicons to Font Awesome, plus the remaining
  neo-* layout classes. All from the theme, without touching the modules.

* Sun Oct 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-16
- Modern theme: animate the sidebar accordion (grid 0fr->1fr, 400ms
  cubic-bezier) so opening a group while another closes is gradual and the eye
  can follow the level-2 items, like Issabel 5. Closed groups are not focusable.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-15
- Legacy themes (tenant, farsi_rtl): use the official Issabel brand purple
  (#572364) in their purple palette too.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-14
- Modern theme: use the official Issabel brand purple (#572364) for the
  primary/sidebar/link tokens (was #522b76).

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-13
- Modern theme: bring the legacy .button class (module forms) to parity with the
  grid buttons (metrics, hover, focus ring), normalise <input class="button"> and
  paint the primary action (name=save/submit_save/submit_apply) with the brand
  colour. Hide the user chip extension sub-line when the user has no extension
  (e.g. admin) instead of showing a bare "#".

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-12
- issabel-dbprocess: ismysqlRunning() checks /usr/sbin/pidof (EL10 merges /sbin)
  and accepts the mariadbd process name.
- Modern theme: add the page header (.iss-pagehead) with breadcrumb and page
  title, an empty state for the grid, an Issabel 5 style login (white/purple
  split with the animated Lottie logo and the centered caret), and fix the help
  frame stylesheet path (themes/default is no longer shipped).

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-11
- Style the legacy .button class (native module buttons) and legacy grid buttons
  (neo-table-toolbar-button); make grid action buttons brand-coloured and force
  white text on colour-coded (ocolor) buttons.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-10
- Modern theme: nested sidebar accordion (2nd-level parents collapse their
  children; exclusive groups and submenus), a Dashboard quick link, a
  notifications badge, and a card look for the grid.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-9
- Expand modern-compat.css with common Bootstrap 3 utilities (text-right/left,
  hidden, caret, list-inline, btn-xs, progress-bar variants).

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-8
- Migrate paloSantoForm widgets to Bootstrap 5 (radio, select, datetimepicker and
  checkbox) and drop the deprecated theme cases from paloSantoNavigation.

* Sat Oct 10 2026 Issabel Foundation <info@issabel.org> - 6.0.0-7
- Add the "modern" GUI theme (Bootstrap 5 + Tabler) and make it the default via
  load_theme(); deprecate tenant/farsi_rtl as non-selectable themes while keeping
  their assets for path compatibility.

* Fri Oct 09 2026 Issabel Foundation <info@issabel.org> - 6.0.0-6
- Drop the orphan postfix_stats.log and its logrotate config, plus the dead
  Cyrus helpers (obtenerClaveCyrusAdmin, isPostfixToIssabel2), configs/email.conf.php
  and the cyrus-imapd entry in the Mail package group: Issabel 6 no longer
  ships a local mail server.
* Fri Oct 09 2026 Issabel Foundation <info@issabel.org> - 6.0.0-5
- getSmarty(): prefer /usr/share/php/smarty4/ (EPEL php-smarty4) over the old
  /usr/share/php/Smarty/ (php-Smarty 3), which creates dynamic properties and
  triggers PHP 8.2+ deprecations.
- Theme index.tpl and userlist edit_userExtension.tpl: default HEADER_MODULES
  to '' so rendering without a module loaded no longer warns.
* Fri Oct 09 2026 Issabel Foundation <info@issabel.org> - 6.0.0-4
- Require the unversioned PHP packages (php-*, 8.3 on EL10) instead of the
  versioned php8.4-*, which conflict with the PHP 8.3 in the netinstall base.
