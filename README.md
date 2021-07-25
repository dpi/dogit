# doGit

![Codecov](https://img.shields.io/codecov/c/github/dpi/dogit)
![GitHub](https://img.shields.io/github/license/dpi/dogit)
![GitHub branch checks state](https://img.shields.io/github/checks-status/dpi/dogit/1.x)

_[Drupal.org](https://www.drupal.org/) + Git CLI application._

[doGit](https://dogit.dev) assists in making the transition to merge requests, and general Git operations, easier for [Drupal](https://www.drupal.org/) developers. 

doGit is typically required globally with [Composer](https://getcomposer.org/).

```shell
composer global require dpi/dogit 
```

Various commands are included:

 - [**Convert** a Drupal.org issue with existing patches to a Git branch][wiki-GitCommand], ready to be pushed as a new merge request, as `dogit git ISSUE-ID`.
 - [Interactively **clone** a merge request of a project][wiki-CloneProjectMergeRequest], as `dogit project:clone:mr PROJECT`.
 - [Interactively **clone** a merge request of an issue][wiki-IssueMergeRequest], as `dogit issue:clone ISSUE-ID`.
 - [**Clone** a project][wiki-ProjectCloneCommand], as `dogit project:clone PROJECT`.
 - [**Show** an issue timeline][wiki-IssueTimelineCommand], as `dogit issue:timeline ISSUE-ID`.

Start with the [wiki](https://github.com/dpi/dogit/wiki), or run `dogit list` or `dogit COMMAND --help`
 
_Drupal is a registered trademark of Dries Buytaert._

[wiki-GitCommand]: https://github.com/dpi/dogit/wiki/Issue-Patches-to-Git-Branch-Command
[wiki-CloneProjectMergeRequest]: https://github.com/dpi/dogit/wiki/Clone-Project-Merge-Request-Command
[wiki-IssueMergeRequest]: https://github.com/dpi/dogit/wiki/Clone-Issue-Merge-Request-Command
[wiki-ProjectCloneCommand]: https://github.com/dpi/dogit/wiki/Clone-Project-Command
[wiki-IssueTimelineCommand]: https://github.com/dpi/dogit/wiki/Show-Issue-Timeline-Command
