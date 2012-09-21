# Optimized Autocomplete

Optimized Autocomplete is a CiviCRM extension. If you have a large database (over 500,000 contacts), you may have noticed that the autocomplete contact search box in top left of CiviCRM will never finish. This is because by default the MySQL queries that are being called to do the search are inefficient on large databases.

This extension fixes that problem by using a temporary table and making a series of efficient MySQL queries instead. However it also makes it so you can no longer customize the autocomplete search preferences. Only install this if you are having scaling issues related to the size of your database.

### Requirements

* CiviCRM 4.2+

### Installation

* Make sure your CiviCRM Extensions Directory is set: civicrm/admin/setting/path?reset=1
* Make sure your CiviCRM Extension Resource URL is set: civicrm/admin/setting/url?reset=1

Before this extension is stable (now):

* Download this extension: https://github.com/micahflee/org.eff.optimizedautocomplete/zipball/master
* Extract it to your CiviCRM Extensions Directory
* Load your CiviCRM Extensions page: civicrm/admin/extensions?reset=1
* Find Optimized Autocomplete in the list and click Install

After this extension is stable:

* Load your CiviCRM Extensions page: civicrm/admin/extensions?reset=1
* Find Optimized Autocomplete in the list and click Install

### How to Use

Optimized Autocomplete searches for contacts based on sort\_name and email.

If you have a contact with first\_name "Micah" and last\_name "Lee", the sort\_name is "Lee, Micah". So if you're searching for that contact, start with the last name first, e.g. "lee, mi" should find this contact. 

You can also just start typing an email address. If a contact has an email address "micah@eff.org", then you can search for "micah@e" and it should find it.

The the wildcard character is at the end of you search phrase. So searching for "micah@e" will find micah@eff.org, but searching for "icah@eff.org" never will. You're welcome to insert your own wildcards if you really want (you can search for "%icah@eff.org"), but it will significantly slow down your query.

