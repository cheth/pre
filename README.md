# pre
HTML(+) preprocessor

# pre?

The preprocessor _pre_ is a general-purpose tool optimized for the the production of web-ready HTML. It produces static web pages--that are often fully database-driven.

# Features
* Very fast web pages
* Highly secure
* MySQL integration for "dynamic" content
* Command line and CMS interfaces
* Simple syntax; easy to use

# Syntax
 #include <filename>
 
# pre History

The first version of _pre_ was created circa 1990 as an object code patch to Borland's Turbo "C" preprocessor. The patch changed the output filename extension to facilitate development of Clipper (a dBase compiler) applications. Much of the underlying philosophy of preprocessors can be traced back to "C."

By 1997 I was deeply involved in web development projects, and increasingly aware that hand-copying the same header and footer for dozens of HTML files was neither efficient nor wise. Unfortunately my Turbo C patch did not have enough room for the four character extension ".html" so I undertook the process of creating a new preprocessor in my preferred language of that moment, Perl. (If HTML were a three letter acronym it is quite likely this code you are reading about now would never have existed.)

By 2000 I had switched from Perl to PHP and inmcreasingly found myself dependant on MySQL. I rewrote the processor in PHP, adding support for MySQL.

In 2003 I wrapped _pre_ inside a content management system, KeepItCurrent, so that users could dynamically alter their content, and still deliver it as static pages.

Meanwhile computers kept getting faster. I began to wonder if there was any point in making static web pages when everyday I heard about some new, more powerful server. I kept a few clients using it, mostly because it was easy to use. But development languished for a number of years.

In 2008 I implemeted my first Drupal site. It was very successful; but it was frequently targeted by nefarious actors, required constant patches, and the original concept of global participation was eventually reduced to one single contributor. I vowed I would never use another commercial CMS.

I have always strived for performant code; to me that typically meant avoiding linear searches in MySQL. But by 2014 I found that my web clients were increasingly concerned about slow loading pages. CDNs, image sprites, CSS consolidation, javascript to the bottom, and numerous other tricks began taking much of my time.

In 2016 I rewrote _pre_ again, this time in object-oriented PHP and as this open source project.

