=== Database Collation Fix ===
Contributors: serverpress, spectromtech, davejesch, Steveorevo
Donate link: https://serverpress.com
Tags: database, migration, collation algorithm, utf8mb4_unicode_520_ci, desktopserver, export, import, moving data, staging
Requires at least: 4.6
Requires PHP: 5.3.1
Tested up to: 5.7
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Convert tables using utf8mb4_unicode_520_ci or utf8_unicode_520_ci collation to a more portable Collation Algorithm.

== Description ==

Since version 4.2, WordPress has been detecting the MySQL version and if it's version 5.5.3 or greater,
automatically selecting the 'utf8mb4_unicode_520_ci' Collation Algorithm. This works well until you need
to migrate your database to an older version of MySQL that does not support the utf8mb4 algorithms. Then,
you run into the error message: "#1273 - Unknown collation: 'utf8mb4_unicode_520_ci'" when importing your database.

With the WordPress 5.7 update and WooCommerce 5.1, some users are reporting an error: "SQLSTATE[HY000]: General
error: 1267 Illegal mix of collations (utf8mb4_unicode_520_ci,IMPLICIT) and (utf8mb4_unicode_ci,IMPLICIT)". The
Database Collation Fix tool also fixes this issue by changing the collation of all columns in your database to
use the same algorithm, removing the "mix" of collations.

<strong>Usage Scenarios:</strong>

While the plugin will work in any WordPress install: local, staging or live, it is specially designed to work with DesktopServer. Its process will be triggered and change the collation types on all database tables during any DesktopServer Create Site, Copy Site, Move Site, Import and Export operations. This allows you to import and export sites in the most compatible ways during deployments. If you would like to use this with DesktopServer as a Design Time plugin, you can install this in your /xampplite/ds-plugins/ directory and it can then be automatically activated and used with all of your local development web sites. For more information on DesktopServer and local development tools, please visit our web site at: <a href="https://serverpress.com/get-desktopserver/">https://serverpress.com/get-desktopserver/</a>.

Alternatively, you can install this as a regular WordPress plugin on any site. Once activated, all of your database tables will be updated to use the more portable Collation Algorithm. If you are migrating your web site, you can install and activate the plugin then perform your database export. Once you have migrated your site, you can deactivate and remove the plugin as it would be no longer needed. If you will be exporting and/or migrating your site repeatedly, such as when using it on a test or staging install, you can leave the plugin active indefinitely and it will continue to monitor and update your database tables automatically, allowing you to perform migrations at any time. This is ideal in situations where you are installing or testing plugins that may create their own database tables, as these tables may be created with the newer Collation Algorithms that are not as portable.

<strong>How it Works:</strong>

The <em>Database Collation Fix</em> tool converts database tables using 'utf8mb4_unicode_520_ci' or 'utf8_unicode_520_ci' Collation Algorithms to a more portable 'utf8mb4_unicode_ci' collation on a once daily basis. It also modifies any column-specific collation statements, not just the default table collation. This means that you can install this plugin and it will continue to monitor all of your database tables and convert them to the more portable Collation Algorithm automatically.

This tool will convert your database tables and columns to use the 'utf8mb4_unicode_ci' Collation Algorithm. This can be modified to any other Collation Algorithm you wish by updating your `wp-config.php` file and adding or changing the following setting:

>`define('DB_COLLATE', 'utf8_general_ci');`

You can use 'utf8_general_ci' or 'utf8' or any other Collation Algorithm supported by your database. See <a href="https://dev.mysql.com/doc/refman/5.7/en/charset-mysql.html">https://dev.mysql.com/doc/refman/5.7/en/charset-mysql.html</a> for a full description of MySQL's Character Set and Collation Algorithm selections.

<strong>Support:</strong>

><strong>Support Details:</strong> We are happy to provide support and help troubleshoot issues. Visit our Contact page at <a href="http://serverpress.com/contact/">http://serverpress.com/contact/</a>. Users should know however, that we check the WordPress.org support forums once a week on Wednesdays from 6pm to 8pm PST (UTC -8).

ServerPress, LLC is not responsible for any loss of data that may occur as a result of using this tool. We strongly recommend performing a site and database backup before testing and using this tool. However, should you experience such an issue, we want to know about it right away.

We welcome feedback and Pull Requests for this plugin via our public GitHub repository located at: <a href="https://github.com/ServerPress/databasecollationfix">https://github.com/ServerPress/databasecollationfix</a>

== Installation ==

Installation instructions: To install, do the following:

1. From the dashboard of your site, navigate to Plugins --&gt; Add New.
2. Select the "Upload Plugin" button.
3. Click on the "Choose File" button to upload your file.
3. When the Open dialog appears select the databasecollationfix.zip file from your desktop.
4. Follow the on-screen instructions and wait until the upload is complete.
5. When finished, activate the plugin via the prompt. A confirmation message will be displayed.

or, you can upload the files directly to your server.

1. Upload all of the files in `databasecollationfix.zip` to your  `/wp-content/plugins/databasecollationfix` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Is this safe? =

Yes. The Database Collation Fix tool does not change any data. It only changes the Collation
Algorithm that specified for your database columns and indexes.

= Do I need to backup my data before using this? =

Yes. Always backup your site before making database changes. The Database Collation Fix tool
is unlikely to cause any problems but there is still a small chance that something else (like
the version of MySQL/MariaDB that you're using) can have a compatibility issue.

= Once my tables are fixed, do I still need to use this tool? =

No. The Database Collation Fix tool changes the database. It only needs to do this once. However,
future versions of WordPress or one or more of your plugins can also make database changes. These
future changes may require updates to keep your Collation Algorithms updated. If you leave the
Database Collation Fix tool active, it will scan your database once per day and look for any tables
that need to be adjusted, fixing them automatically.

== Screenshots ==

1. Plugin page.

== Changelog ==
= 1.2.6 - Aug 2, 2018 =
Add handling for FULLTEXT indexes.

= 1.2.5 - May 11, 2018 =
Add handling for 'enum' column type when looking for things to update.

= 1.2.4 - Jul 26, 2017 =
Add feature to allow user to select Collation Algorithm for on demand updates.

= 1.2.2 - Jul 12 2017 =
Fix error display by not scheduling cron during WP install.

= 1.2 - Feb 24, 2017 =
Initial release to WordPress repository.

= 1.1 - Dec 13, 2016 =
* check for non-empty DB_COLLATION specification.

= 1.0 - Oct 14, 2016 =
* Initial Release

== Upgrade Notice ==

= 1.2 =
First release.
