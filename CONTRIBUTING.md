# Contributing 

## Reporting Bugs

If you happen to find a bug, we kindly request you to report it. However, before submitting it, please:

* Check if the bug is not already reported!
* A clear title to resume the issue
* A description of the workflow needed to reproduce the bug

### Matching Coding Standards

Don't worry, you can fix CS issues automatically using the [PHP CS Fixer](https://cs.sensiolabs.org/) tool:

    vendor/bin/php-cs-fixer fix

And then, add the fixed file to your commit before pushing.
Be sure to add only **your modified files**. If any other file is fixed by cs tools, just revert it before committing.

### Sending a Pull Request

When you send a PR, just make sure that:

* You add valid test cases.
* Tests are green.
* You make the PR on the same branch you based your changes on. If you see commits
  that you did not make in your PR, you're doing it wrong.
