-- =====================================================================
-- BayanAlert PH - Go-Live Cleanup Script
-- =====================================================================
-- Run this ONCE on your live database after you've finished testing with
-- the demo/sample data and are ready to open the site to real users.
--
-- WHAT THIS DOES:
--   1. Deletes the 3 built-in demo accounts (admin/responder/citizen@bayanalert.test)
--   2. Deletes all sample evacuation centers and emergency facilities
--      (the ones flagged is_sample = 1 in database.sql)
--   3. Deletes the 3 sample incident reports seeded by database.sql
--   4. Deletes the 3 sample alerts (titled "[DEMO] ...")
--   5. Renames the one sample-labeled emergency contact row
--
-- IMPORTANT - READ BEFORE RUNNING:
--   - This assumes you are running it on a database that still has the
--     ORIGINAL data from database.sql (i.e. you have not already deleted
--     or renumbered the demo rows). If you're unsure, back up your
--     database first (phpMyAdmin > Export).
--   - After running this, there will be NO admin account left. Use
--     admin-setup.php (in the project root) immediately afterward to
--     create your real admin account, then DELETE admin-setup.php from
--     the server. See README.md / DEPLOY notes for details.
--   - Real evacuation centers, facilities, alerts, and reports that YOU
--     added through the admin panel are NOT affected - only the original
--     seeded demo/sample rows are removed.
-- =====================================================================

-- 1. Remove the 3 sample incident reports seeded by database.sql.
--    (These are reliably rows 1-3 only on a database that still has the
--    original seed data and nothing else added before this point.)
DELETE FROM incident_reports WHERE id IN (1, 2, 3)
  AND description IN (
    'Ankle-deep flooding along the main road after heavy rain.',
    'Fallen tree branch blocking one lane.',
    'Small fire reported near a residential compound, sample data.'
  );

-- 2. Remove the 3 demo accounts (admin, responder, citizen).
--    incident_reports.reporter_id for any real reports made by these
--    accounts will be set to NULL automatically (ON DELETE SET NULL) -
--    harmless, but double-check you don't need those reports first.
DELETE FROM users WHERE email IN (
  'admin@bayanalert.test',
  'responder@bayanalert.test',
  'citizen@bayanalert.test'
);

-- 3. Remove all sample evacuation centers and facilities.
DELETE FROM evacuation_centers WHERE is_sample = 1;
DELETE FROM emergency_facilities WHERE is_sample = 1;

-- 4. Remove the 3 sample alerts.
DELETE FROM alerts WHERE title LIKE '[DEMO]%';

-- 5. Clean up the one sample-labeled emergency contact (keep the row,
--    just drop the "(Sample)" label and update the number to your real one).
UPDATE emergency_contacts
SET label = 'City DRRM Office', number = 'UPDATE THIS NUMBER'
WHERE label LIKE '%(Sample)%';

-- Done. Next step: visit admin-setup.php on your live site to create
-- your real administrator account, then delete admin-setup.php.
