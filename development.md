---
title: Development
has_children: false
nav_order: 5
---

# Development

## Distributed Version Control System (DVCS)

For the Distributed Version Control System (DVCS) of this project, Git was used, with GitHub as the remote. The development process was kept simple: the new features, bug fixes, and other changes were committed to the main branch, with a few extra branches only for the report site.

To keep the project's history clear, the commit messages followed the Conventional Commits format `<type>: <subject>`, where the type could be `feat`, `fix`, `docs`, `test`, `style`, or `chore`, and the subject is a brief description of the change.

The application and this report are kept in two separate repositories, so that there is only one copy of the report.

## Implementation details

The project started as a plain procedural PHP application, with SQL, HTML, and logic mixed together in each page. As it grew, most of the effort went into moving the shared logic into `includes/expense-helpers.php`, which now handles currencies, output escaping, prepared statements, CSRF protection, budgets, and recurring expenses.

The biggest "surprise" was the recurring expenses. Adding one expense when a rule is due was not enough: if the application was not opened for a while, a monthly bill that fell due three times produced only one record. The final version moves the next run date forward in a loop and creates one expense for every period that has passed.

Another problem was that new columns were added while older databases were still in use. To avoid rebuilding the database by hand, a helper adds any missing columns and tables when a page needs them. This was practical locally, but it leaves no migration history, and I would not do it again on a system with more than one deployment.

In the end, the application reached its goal. The authentication and detailed report pages still use the older code and could be moved to the helpers, but this work exceeds the scope of this project.
