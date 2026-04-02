# InfinityFree Setup

# 1. Create the database
- In your InfinityFree control panel, create a MySQL database.
- Copy the database host, name, username and password.
- The app is already configured with the database host, name, user and password you provided.

## 2. Import the schema
- Open phpMyAdmin from InfinityFree.
- Select your database.
- Use a new or empty database so the updated tables are created cleanly.
- Import the file `schema.sql`.
- This creates:
  - `users`
  - `training_days`
  - `training_routines`
  - `training_matches`

## 3. Configure the app
- Open `config.php`.
- Verify that the database host, name, user and password match your InfinityFree panel.

## 4. Upload the files
- Upload everything except the old static `index.html` if it still exists locally.
- Make sure these files are in the public folder:
  - `index.php`
  - `actions.php`
  - `config.php`
  - `db.php`
  - `helpers.php`
  - `styles.css`
  - `app.js`
  - `.htaccess`
  - `schema.sql`

## 5. Open the site
- Visit your InfinityFree domain.
- Register a user.
- Log in.
- Add your sessions.

## 6. Notes
- The app uses PHP sessions for authentication.
- Training data is stored in MySQL, not in localStorage.
- Each saved day stores a daily header plus routine items and match items.
- The chart is generated from the saved daily totals.
