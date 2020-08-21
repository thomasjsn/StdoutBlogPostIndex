# StdoutBlogPostIndex

Custom MediaWiki special page extension for https://www.stdout.no, based on [Newest Pages](https://www.mediawiki.org/wiki/Extension:Newest_Pages).

## Installation
* Clone this repository in s directory called `StdoutBlogPostIndex` in your `extensions/` folder.
* Add the following code at the bottom of your `LocalSettings.php`:
```
wfLoadExtension( 'StdoutBlogPostIndex' );
```
* Done! – Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

## Include
```
{{Special:BlogPostIndex}}
```

## Author
[Thomas Jensen](https://thomas.stdout.no)
