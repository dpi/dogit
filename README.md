# doGit

[![Latest Stable Version](http://poser.pugx.org/dpi/dogit/v)](https://packagist.org/packages/dpi/dogit)
[![Total Downloads](http://poser.pugx.org/dpi/dogit/downloads)](https://packagist.org/packages/dpi/dogit)
[![Codecov](https://img.shields.io/codecov/c/github/dpi/dogit)][code-coverage]
[![GitHub branch checks state](https://img.shields.io/github/checks-status/dpi/dogit/1.x)][ci]
[![License](http://poser.pugx.org/dpi/dogit/license)](https://packagist.org/packages/dpi/dogit)

_[Drupal.org](https://www.drupal.org/) + Git CLI application._

[doGit](https://dogit.dev) assists in making the transition to merge requests, and general Git operations, easier for [Drupal](https://www.drupal.org/) developers. 

doGit is typically required globally with [Composer](https://getcomposer.org/).

```shell
composer global require dpi/dogit 
```

Various commands are included:

 - [**Convert** a Drupal.org issue with existing patches to a Git branch][wiki-GitCommand], ready to be pushed as a new merge request, as `dogit git ISSUE-ID`.
 - [Interactively **clone** or **checkout** a merge request of a project][wiki-ProjectMergeRequest], as `dogit project:mr PROJECT`.
 - [Interactively **clone** or **checkout** a merge request of an issue][wiki-IssueMergeRequest], as `dogit issue:mr ISSUE-ID`.
 - [**Clone** a project][wiki-ProjectCloneCommand], as `dogit project:clone PROJECT`.
 - [**Show** an issue timeline][wiki-IssueTimelineCommand], as `dogit issue:timeline ISSUE-ID`.

Start with the [wiki](https://github.com/dpi/dogit/wiki), or run `dogit list` or `dogit COMMAND --help`
 
_Drupal is a registered trademark of Dries Buytaert._

[ci]: https://github.com/dpi/dogit/actions
[code-coverage]: https://app.codecov.io/gh/dpi/dogit
[wiki-GitCommand]: https://github.com/dpi/dogit/wiki/Issue-Patches-to-Git-Branch-Command
[wiki-ProjectMergeRequest]: https://github.com/dpi/dogit/wiki/Project-Merge-Request-Command
[wiki-IssueMergeRequest]: https://github.com/dpi/dogit/wiki/Issue-Merge-Request-Command
[wiki-ProjectCloneCommand]: https://github.com/dpi/dogit/wiki/Clone-Project-Command
[wiki-IssueTimelineCommand]: https://github.com/dpi/dogit/wiki/Show-Issue-Timeline-Command
