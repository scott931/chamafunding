-- ============================================================================
-- PostgreSQL Script to Make scottmkari@gmail.com a Super Admin
-- ============================================================================
-- This script ensures the Super Admin role exists and assigns it to the user
-- 
-- Usage:
--   psql -U your_username -d your_database -f make_super_admin.sql
--   OR
--   Copy and paste into your PostgreSQL client (pgAdmin, DBeaver, etc.)
-- ============================================================================

-- Step 1: Ensure the "Super Admin" role exists
-- If it doesn't exist, create it with guard_name 'web'
INSERT INTO roles (name, guard_name, created_at, updated_at)
VALUES ('Super Admin', 'web', NOW(), NOW())
ON CONFLICT (name, guard_name) DO NOTHING;

-- Step 2: Remove any existing role assignments for this user
-- (This ensures clean assignment - comment out if you want to keep existing roles)
DELETE FROM model_has_roles
WHERE model_id = (SELECT id FROM users WHERE email = 'scottmkari@gmail.com')
  AND model_type = 'App\\Models\\User';

-- Step 3: Assign the Super Admin role to the user
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT 
    r.id AS role_id,
    'App\\Models\\User' AS model_type,
    u.id AS model_id
FROM users u
CROSS JOIN roles r
WHERE u.email = 'scottmkari@gmail.com'
  AND r.name = 'Super Admin'
  AND r.guard_name = 'web'
ON CONFLICT (role_id, model_id, model_type) DO NOTHING;

-- Step 4: Verify the assignment
SELECT 
    u.id AS user_id,
    u.name AS user_name,
    u.email AS user_email,
    r.name AS role_name,
    r.guard_name
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id
INNER JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'scottmkari@gmail.com'
  AND mhr.model_type = 'App\\Models\\User';

-- ============================================================================
-- ALTERNATIVE: If you want to KEEP existing roles and just ADD Super Admin
-- ============================================================================
-- Use this version instead if you want to preserve other role assignments:
/*
-- Step 1: Ensure the "Super Admin" role exists
INSERT INTO roles (name, guard_name, created_at, updated_at)
VALUES ('Super Admin', 'web', NOW(), NOW())
ON CONFLICT (name, guard_name) DO NOTHING;

-- Step 2: Assign the Super Admin role (without removing existing roles)
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT 
    r.id AS role_id,
    'App\\Models\\User' AS model_type,
    u.id AS model_id
FROM users u
CROSS JOIN roles r
WHERE u.email = 'scottmkari@gmail.com'
  AND r.name = 'Super Admin'
  AND r.guard_name = 'web'
ON CONFLICT (role_id, model_id, model_type) DO NOTHING;

-- Step 3: Verify all roles for the user
SELECT 
    u.id AS user_id,
    u.name AS user_name,
    u.email AS user_email,
    r.name AS role_name,
    r.guard_name
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id
INNER JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'scottmkari@gmail.com'
  AND mhr.model_type = 'App\\Models\\User';
*/

