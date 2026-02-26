# WP Safe User Deletion

A must-use WordPress plugin that adds a double confirmation when deleting users and blocks deletion if the user has content that has not been reassigned to another user.

## Why we created this plugin

Deleting a WordPress user who has authored posts, pages, or other content can permanently delete that content if an administrator chooses "Delete all content" instead of "Attribute all content to" another user. This is easy to do by mistake: the confirmation screen presents both options, and a quick click can lead to irreversible data loss.

We created WP Safe User Deletion to:

- **Reduce accidental data loss** when removing users (e.g. former staff or contributors).
- **Make the reassignment option obvious** so admins are encouraged to assign content to another user before deleting.
- **Enforce a safety check** so deletion is blocked when the user has content and no reassignment was selected.

## What this plugin prevents

- **Permanent loss of posts and pages** – Content authored by the deleted user is no longer lost by mistake when an admin forgets to choose "Attribute all content to".
- **Orphaned or deleted custom content** – The same protection applies to any post type that supports author (e.g. custom post types like job profiles).
- **Rushed deletions** – An extra confirmation step when "Delete all content" is selected gives admins a chance to cancel and reassign instead.

The plugin does **not** prevent deletion when:

- The user has no content (posts, pages, or links), or
- The admin has chosen "Attribute all content to" and selected another user.

In those cases, deletion proceeds as normal.

## How it works

1. **On the delete confirmation screen** (Users → Delete on a user), an admin notice reminds the admin to choose a user to assign content to before deleting, so no data is lost.
2. **Before submitting the form**, if the admin selects "Delete all content", a browser confirmation dialog appears warning that the user has content and suggesting they assign it to another user. They can cancel to go back and choose "Attribute all content to".
3. **When deletion is requested**, the plugin checks whether the user being deleted has any content (posts of any type or, if the link manager is enabled, links). If they do, and no reassignment was selected, the deletion is **blocked** and the admin sees a clear message with a link back to the Users list to try again and select a reassignment user.

This plugin works with WordPress's built-in user delete flow; it does not add new admin pages or change how you delete users, only adds notices and guards.

## What counts as "content"

- **Posts and pages** – Any post type that supports the author field (posts, pages, and custom post types such as job profiles).
- **Links** – If the site has the link manager enabled, links owned by the user are also considered content.

If the user has at least one such item, deletion is blocked until content is reassigned (or the user is deleted via the reassign option).

## Requirements

- WordPress (tested with modern versions; uses standard hooks and APIs).
- No additional PHP extensions or Composer dependencies.
- Installed as a must-use plugin (`mu-plugins`); it is always active and does not appear in the Plugins list.

## Installation

### Composer / Wordpress Bedrock
```bash
composer require pvtl/wp-safe-user-deletion
```

### Manual
1. Upload plugin to `wp-content/plugins/wp-safe-user-deletion/`
2. Activate plugin in WordPress admin

## Author

Pivotal Agency Pty Ltd

## License

MIT