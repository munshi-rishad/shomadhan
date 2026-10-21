# Database updates (migrations)

After the site is live the database has real data, so `setup.bat` (which deletes everything and imports
`schema.sql` again) must **never** be used on it. Every later change to the tables goes here as a small file instead.

## How to add a change
1. Make a file named `YYYY_MM_DD_short_name.sql`, e.g. `2026_11_02_add_provider_nid.sql`.
   Files run in name order, so the date in front keeps them in the right order.
2. Write the SQL. Each statement ends with `;` at the end of its line. Lines starting with `--` are comments.
   ```sql
   -- providers get a national ID number
   ALTER TABLE providers ADD COLUMN nid VARCHAR(20) NULL DEFAULT NULL AFTER address;
   ```
3. Put the same change in `database/schema.sql` too, so a fresh setup already has it.
4. Never edit a file that already ran somewhere: make a new one.

## How to run
- Windows: double-click `update.bat` in the project folder. It first saves a backup to `backups\`, then runs
  only the files that did not run yet. Data is kept.
- Any computer / server: `php tools/update.php` (`--list` shows done / waiting).

`setup.bat` imports `schema.sql` and then marks every file here as done (`php tools/update.php --mark-all`).
