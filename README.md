# doGit


_[Drupal.org](https://www.drupal.org/) + Git CLI application._

[doGit](https://dogit.dev) assists in making the transition to merge requests, and general Git operations, easier for [Drupal](https://www.drupal.org/) developers. 

doGit is typically required globally with [Composer](https://getcomposer.org/).

```shell
composer global require dpi/dogit 
```

Various commands are included:

 - **Convert** a Drupal.org issue with existing patches to a Git branch, ready to be pushed as a new merge request, as `dogit git ISSUE-ID`.
 - Interactively **clone** a merge request of a project, as `dogit project:clone:mr PROJECT`.
 - Interactively **clone** a merge request of an issue, as `dogit issue:clone ISSUE-ID`.
 - **Clone** a project, as `dogit project:clone PROJECT`.
 - **Show** an issue timeline, as `dogit issue:timeline ISSUE-ID`.

Start with `dogit list` or `dogit COMMAND --help`
 
_Drupal is a registered trademark of Dries Buytaert._