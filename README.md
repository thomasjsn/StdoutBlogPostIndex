# StdoutBlogPostIndex

Custom MediaWiki special page extension for https://www.stdout.no, based on [Newest Pages](https://www.mediawiki.org/wiki/Extension:Newest_Pages).

This extension creates a special page where pages in the NS_BLOG namespace is listed chronologically, grouped by year and month, like this:

**June 2020**
* A blog post

**May 2020**
* More blogs posts
* And stuff

> This extension is a work in progress!

## Installation
* Clone this repository in a directory called `StdoutBlogPostIndex` in your `extensions/` folder.
* Add the following code at the bottom of your `LocalSettings.php`:
```
wfLoadExtension( 'StdoutBlogPostIndex' );
```
* Done! – Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

## Include
```
{{Special:BlogPosts}}
```

## Author
[Thomas Jensen](https://stdout.no/thomas)
