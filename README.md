# pre
HTML(+) preprocessor

*pre* is a general-purpose tool optimized for the the production of web-ready HTML. It produces static web pages &mdash; that are often database-driven.

# Features
* Very fast web page delivery
* Highly secure
* MySQL integration for "dynamic" content
* Command line and CMS interfaces
* Simple syntax; easy to use

# Syntax Cheatsheet
All lines are pass-thru, unless the first character is a pound sign (#) followed by one of the recognized keywords listed below.
```
##  <comment>

#coolclass <filename> <classname>
#cooltext <functionname> <text>

#define <key> <value>
#define <key> <<
<multi-line defines>
>>

#eval

#extension <.extension>

#if <conditional clause>
#else
#endif

#include <filename>

#mysqlopen <credentials>
#select-one <sql>

#select-one >>
<multi-line sql>
<<

#select <sql>
<line(s) processed for each selected row>
#endselect

#select >>
<multi-line sql>
<<
<line(s) processed for each selected row>
#endselect
```

# pre <> View
Unlike Markdown, *pre* is not aimed at the View. From the Model-View-Controller perspective, *pre* acts more like Controller: *pre* hauls in blocks of code, acquires database rows, executes conditional logic, and cycles through loops. The goal of *pre* is to perform the tasks necessary to support multi-page sites, not to make a particular page pretty.

Making pages pretty is important, of course. To aid prettification *pre* supports filtering selected portions of text through a Markdown or Markdown-like filter.

# pre History

The first version of *pre* was created circa 1990 as an object code patch to Borland's Turbo "C" preprocessor. The patch changed the output filename extension to facilitate development of Clipper (a dBase compiler) applications.

By 1997 I was deeply involved in web development projects, and increasingly aware that hand-copying the same header and footer for dozens of HTML files was neither efficient nor wise. Unfortunately my Turbo C patch did not have enough room for the four character extension ".html" so I undertook the process of creating a new preprocessor in my preferred language of that moment, Perl. (If HTML were a three letter acronym it is quite likely *pre* would never have existed.)

By 2000 I switched web development from Perl to PHP. I rewrote the processor in PHP, adding support for MySQL.

In 2003 I wrapped *pre* inside a content management system, KeepItCurrent, so that users could dynamically alter their content.

Meanwhile computers kept getting faster. I began to wonder if there was any point in making static web pages when everyday I heard about some new, more powerful computer. A few clients kept using it. But development languished for a number of years.

In 2008 I implemeted my first Drupal site. It was very successful; but frequently targeted by nefarious actors, required constant patches, and, as a result, the original concept of global participation was eventually reduced to one single contributor. I vowed to never use another commercial CMS.

I always strived for performant code; to me that typically meant avoiding linear searches in MySQL. But by 2014 I found that web clients were increasingly concerned about slow loading pages. CDNs, image sprites, CSS consolidation, javascript to the bottom, and numerous other tricks began consuming much of my time.

In 2016 I revised *pre* again, as this open source project.
