# Demon Slayer Fan Hub

A PHP/MySQL fan hub for the *Demon Slayer: Kimetsu no Yaiba* anime, where users can browse characters, mark favorites, and leave star ratings and reviews. Admin accounts can add, edit, and remove characters.

## Tech Stack

- PHP 8 (mysqli, prepared statements)
- MySQL / MariaDB
- Bootstrap 5 (via CDN)
- Vanilla JavaScript (AJAX for the admin edit form)

## Features

- User registration and login with hashed passwords
- Role-based access: regular users vs. admins
- Admins can add, edit, and delete characters
- Users can mark characters as favorites and remove them
- Star-rating review system, including editing and deleting your own reviews
- Search and filter characters by name, rank, race, and status
- AJAX-powered edit form for admins (no page reload)

## Project Structure

```
/
├── index.php              Landing page for logged-in users
├── login.php               Login form and session creation
├── register.php             Registration form
├── logout.php               Destroys the session
├── profile.php               User's favorites and reviews
├── characters.php             Character list, search/filter, add/edit (admin), favorite, review
├── character.php               Single character detail page and admin edit form
├── get_character.php            AJAX endpoint used by the admin edit form
├── reviews.php                   User's own reviews (edit/delete)
├── includes/db.php                Database connection
└── database.sql                    Database schema (tables, indexes, foreign keys)
```

## Database Schema

- `users` — id, username, email, password (hashed), role
- `characters` — id, name, breathing_style, rank, description, race, occupation, status, image_url, debut_episode, gender, age
- `favorites` — links a user to a favorited character
- `reviews` — a user's rating and written review for a character

## Setup (Local, via XAMPP)

1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin` and import `database.sql` to create the `demonslayer_db` database.
3. Copy this project folder into `htdocs`.
4. Update the credentials in `includes/db.php` if your MySQL setup differs from the defaults (`root` / no password).
5. Visit `http://localhost/<project-folder>/` in your browser.

## Security Notes

- All database queries use prepared statements.
- Passwords are hashed with `password_hash()` and verified with `password_verify()`.
- Session checks guard every page that requires a logged-in user.
- Character management actions are restricted to the `admin` role on the server side, not just hidden in the UI.
