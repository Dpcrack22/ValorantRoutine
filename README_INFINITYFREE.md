# Local / InfinityFree Setup

## 1. InfinityFree database
- The app is configured by default for your InfinityFree database.
- Current connection settings:
  - Host: `sql107.infinityfree.com`
  - Port: `3306`
  - Database: `if0_41557585_rutinas`
  - User: `if0_41557585`
- Import the file `schema.sql` into that database from phpMyAdmin.

## 1.1 What goes in Git
- Commit the PHP files, CSS, JS and `schema.sql`.
- Do not commit the live MySQL database files.
- If you need the current data on another machine, export it with `mysqldump` or phpMyAdmin and import that dump on the other side.

## 2. App configuration
- `config.php` already points to your InfinityFree database.
- You can still override the values with environment variables if you need to test somewhere else.
- `db.php` will show a clear error if the connection fails.

## 3. Import the schema
- Use a new or empty database so the tables are created cleanly.
- The schema creates:
  - `users`
  - `training_days`
  - `training_routines`
  - `training_matches`

## 3.1 Moving data between machines
- If you only need the structure, `schema.sql` is enough.
- If you want the same users and sessions on the other machine, export the database from the first machine and import the `.sql` dump into the second one.
- After import, make sure the database name and credentials in `config.php` or environment variables match the target machine.

## 4. Run the app
- Upload the files to InfinityFree.
- Open the site.
- Register a user.
- Log in.
- Add your sessions.

## 5. Notes
- The app uses PHP sessions for authentication.
- Training data is stored in MySQL, not in localStorage.
- Each saved day stores a daily header plus routine items and match items.
- The chart is generated from the saved daily totals.

## 6. Notes
- The app uses PHP sessions for authentication.
- Training data is stored in MySQL, not in localStorage.
- Each saved day stores a daily header plus routine items and match items.
- The chart is generated from the saved daily totals.
