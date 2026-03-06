Social sign-in (Facebook)

This project now uses a direct Facebook sign-in flow that POSTs provider tokens to a server endpoint which verifies and upserts users into your local `auth_users` table.

Files of interest:
- `db.php` - PDO connection to the `searchar` DB.
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

-- Add provider ID column (facebook) and unique email if you plan to accept social sign-ins directly
-- ALTER TABLE auth_users
--   ADD COLUMN facebook_id VARCHAR(255) DEFAULT NULL,
--   ADD UNIQUE KEY uq_email (email),
--   ADD UNIQUE KEY uq_facebook_id (facebook_id);

-- Note: If you use Auth0 as primary identity provider, you may not need facebook_id columns.

Configuration & testing:
- Ensure `db.php` is configured and the `auth_users` table exists (see above SQL).
- Add provider ID column if you accept social sign-ins directly:
  ALTER TABLE auth_users
    ADD COLUMN facebook_id VARCHAR(255) DEFAULT NULL,
    ADD UNIQUE KEY uq_email (email);

Testing locally:
1. Include Facebook SDK and initialize with your App ID.
2. Open the signup page, select a role, click Facebook.
3. The page should POST to `Php/facebook-signin.php` and return a JSON response. Verify DB changes.

Security notes:
- Verify provider tokens on the server before creating/updating users. Do not trust client-side role values without server-side validation.
- Consider limiting which roles can be self-assigned (e.g., only allow `user` by default and require admin approval for sensitive roles).
