Social sign-in (Google / Facebook)

This project now uses direct Google and Facebook sign-in flows that POST provider tokens to server endpoints which verify and upsert users into your local `auth_users` table.

Files of interest:
- `db.php` - PDO connection to the `searchar` DB.
- `google-signin.php` - accepts POST { credential: ID_TOKEN, role } and verifies Google ID token, upserts user.
- `facebook-signin.php` - accepts POST { access_token, role } and verifies FB token via Graph API, upserts user.

Database schema (create `auth_users` table):

CREATE TABLE `auth_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `auth0_id` VARCHAR(128) NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `picture` VARCHAR(512) DEFAULT NULL,
  `role` ENUM('user','policeman','volunteer','camera_contributor') DEFAULT 'user',
  `refresh_token` TEXT DEFAULT NULL,
  `token_expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_auth0_id` (`auth0_id`)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- If you already have `auth_users` table, run this migration to add refresh token columns:
-- ALTER TABLE auth_users ADD COLUMN refresh_token TEXT DEFAULT NULL, ADD COLUMN token_expires_at DATETIME DEFAULT NULL;
-- If you already have `auth_users` table, run this migration to add refresh token columns:
-- ALTER TABLE auth_users ADD COLUMN refresh_token TEXT DEFAULT NULL, ADD COLUMN token_expires_at DATETIME DEFAULT NULL;

-- Add provider ID columns (google/facebook) and unique email if you plan to accept social sign-ins directly
-- ALTER TABLE auth_users
--   ADD COLUMN google_id VARCHAR(255) DEFAULT NULL,
--   ADD COLUMN facebook_id VARCHAR(255) DEFAULT NULL,
--   ADD UNIQUE KEY uq_email (email),
--   ADD UNIQUE KEY uq_google_id (google_id),
--   ADD UNIQUE KEY uq_facebook_id (facebook_id);

-- Note: If you use Auth0 as primary identity provider, you may not need google_id/facebook_id columns.

Configuration & testing:
- Ensure `db.php` is configured and the `auth_users` table exists (see above SQL).
- Add provider ID columns if you accept social sign-ins directly:
  ALTER TABLE auth_users
    ADD COLUMN google_id VARCHAR(255) DEFAULT NULL,
    ADD COLUMN facebook_id VARCHAR(255) DEFAULT NULL,
    ADD UNIQUE KEY uq_email (email);

Testing locally:
1. Include Google Identity Services script and set your Google client id in the page (or render it server-side):
   <script src="https://accounts.google.com/gsi/client" async defer></script>
   <script>window.GOOGLE_CLIENT_ID = 'YOUR_GOOGLE_CLIENT_ID';</script>
2. Include Facebook SDK and initialize with your App ID.
3. Open the signup page, select a role, click Google or Facebook.
4. The page should POST to `Php/google-signin.php` or `Php/facebook-signin.php` and return a JSON response. Verify DB changes.

Security notes:
- Verify provider tokens on the server before creating/updating users. Do not trust client-side role values without server-side validation.
- Consider limiting which roles can be self-assigned (e.g., only allow `user` by default and require admin approval for sensitive roles).
