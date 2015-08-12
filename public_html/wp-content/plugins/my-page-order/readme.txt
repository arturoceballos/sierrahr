=== My Page Order ===
Contributors: froman118
Donate link: http://geekyweekly.com/mypageorder
Tags: page, order, sidebar, widget
Requires at least: 2.5
Tested up to: 2.7
Stable tag: 2.7

My Page Order allows you to set the order of pages through a drag and drop interface. 

== Description ==

My Page Order allows you to set the order of pages through a drag and drop interface. The default method
of setting the order page by page is extremely clumsy, especially with a large number of pages.

= Change Log =

2.7:

* Updated for 2.7, now under the the new Page menu.
* Unpublished pages now show up in the Subpage dropdown (thanks Josef)
* Moved to jQuery for drag and drop
* Removed finicky AJAX submission
* Added missing translation phrase to POT, send me updated MO files and help fill in missing translations
* Translations added and thanks: Russian (Flector), French (Merimac), Persian (Mohammad and Mohammad), Dutch (Anja).

2.6.1:

* Localized strings and added .po files for translation. If you are interested in translating send me an email.

== Installation ==

1. Upload plugin contents to /wp-content/plugins/my-page-order
2. Activate the My Page Order plugin on the Plugins menu
3. Go to the "My Page Order" tab under Manage and specify your desired order for pages
4. If you are using widgets then just make sure the "Page" widget is set to order by "Page order". That's it.
5. If you aren't using widgets, modify your sidebar template to use correct sort order: `wp_list_pages('sort_column=menu_order&title_li=');`


== Frequently Asked Questions ==

= Why isn't this already built into WP? =

I don't know. Hopefully it will be in a future release in one form or another because the current
method is less than ideal.

