# Theme translations

`.po` and `.mo` files for the `{{SLUG}}-child` text domain live here, in git, never in the database.
The core plugin's strings (`{{SLUG}}-core`) live in `mu-plugins/{{SLUG}}-core/languages/`.
Make the `.pot` with `ddev wp i18n make-pot`, translate it, and compile the `.mo` with `ddev wp i18n make-mo`.
