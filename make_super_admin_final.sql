-- ============================================================================
-- FINAL PostgreSQL Script - Make scottmkari@gmail.com a Super Admin
-- ============================================================================
-- Based on actual project structure:
-- - Table: users (id, email, name, ...)
-- - Table: roles (id, name, guard_name, ...)
-- - Table: model_has_roles (role_id, model_id, model_type)
-- - Primary Key: (role_id, model_id, model_type)
-- - Constraint Name: model_has_roles_role_model_type_primary
-- - Model Type: App\Models\User
-- - Guard Name: web
-- ============================================================================

-- Step 1: Ensure the "Super Admin" role exists
INSERT INTO roles (name, guard_name, created_at, updated_at)
VALUES ('Super Admin', 'web', NOW(), NOW())
ON CONFLICT (name, guard_name) DO NOTHING;

-- Step 2: Assign Super Admin role to the user
-- This is the BEST version - uses proper JOIN and handles all edge cases
-- Note: model_type uses single backslash - PostgreSQL stores it as-is in single quotes
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT 
    r.id,
    'App\Models\User',
    u.id
FROM users u
INNER JOIN roles r ON r.name = 'Super Admin' AND r.guard_name = 'web'
WHERE u.email = 'scottmkari@gmail.com'
ON CONFLICT (role_id, model_id, model_type) DO NOTHING;

-- Step 3: Verify the assignment
SELECT 
    u.id AS user_id,
    u.name AS user_name,
    u.email AS user_email,
    r.name AS role_name,
    r.guard_name,
    mhr.model_type
FROM users u
INNER JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\Models\User'
INNER JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'scottmkari@gmail.com';

-- ============================================================================
-- ALTERNATIVE: If you want to remove existing roles first
-- ============================================================================
/*
-- Remove all existing roles for this user
DELETE FROM model_has_roles
WHERE model_id = (SELECT id FROM users WHERE email = 'scottmkari@gmail.com')
  AND model_type = 'App\Models\User';

-- Then assign Super Admin
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT 
    r.id,
    'App\Models\User',
    u.id
FROM users u
INNER JOIN roles r ON r.name = 'Super Admin' AND r.guard_name = 'web'
WHERE u.email = 'scottmkari@gmail.com'
ON CONFLICT (role_id, model_id, model_type) DO NOTHING;
*/

-- ============================================================================
-- ONE-LINER VERSION (if role already exists)
-- ============================================================================
/*
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\Models\User', u.id
FROM users u, roles r
WHERE u.email = 'scottmkari@gmail.com' 
  AND r.name = 'Super Admin' 
  AND r.guard_name = 'web'
ON CONFLICT (role_id, model_id, model_type) DO NOTHING;
*/

