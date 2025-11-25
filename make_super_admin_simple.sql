-- ============================================================================
-- SIMPLE PostgreSQL Script to Make scottmkari@gmail.com a Super Admin
-- ============================================================================
-- This is a simplified version that's easier to run step-by-step
-- ============================================================================

-- Step 1: First, ensure the Super Admin role exists
INSERT INTO roles (name, guard_name, created_at, updated_at)
VALUES ('Super Admin', 'web', NOW(), NOW())
ON CONFLICT (name, guard_name) DO NOTHING;

-- Step 2: Assign the Super Admin role (simplified version)
-- This version uses a simpler JOIN syntax
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT 
    r.id,
    'App\\Models\\User',
    u.id
FROM users u
JOIN roles r ON r.name = 'Super Admin' AND r.guard_name = 'web'
WHERE u.email = 'scottmkari@gmail.com'
ON CONFLICT (role_id, model_id, model_type) DO NOTHING;

-- Step 3: Verify it worked
SELECT 
    u.email,
    u.name,
    r.name AS role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'scottmkari@gmail.com'
  AND mhr.model_type = 'App\\Models\\User';

-- ============================================================================
-- TROUBLESHOOTING: If you get an error about the constraint
-- ============================================================================
-- If ON CONFLICT doesn't work, try this version that uses the constraint name:
/*
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT 
    r.id,
    'App\\Models\\User',
    u.id
FROM users u
JOIN roles r ON r.name = 'Super Admin' AND r.guard_name = 'web'
WHERE u.email = 'scottmkari@gmail.com'
ON CONFLICT ON CONSTRAINT model_has_roles_role_model_type_primary DO NOTHING;
*/

-- ============================================================================
-- ALTERNATIVE: If you need to check if user/role exists first
-- ============================================================================
/*
DO $$
DECLARE
    v_user_id BIGINT;
    v_role_id BIGINT;
BEGIN
    -- Get user ID
    SELECT id INTO v_user_id FROM users WHERE email = 'scottmkari@gmail.com';
    
    -- Get role ID
    SELECT id INTO v_role_id FROM roles WHERE name = 'Super Admin' AND guard_name = 'web';
    
    -- Create role if it doesn't exist
    IF v_role_id IS NULL THEN
        INSERT INTO roles (name, guard_name, created_at, updated_at)
        VALUES ('Super Admin', 'web', NOW(), NOW())
        RETURNING id INTO v_role_id;
    END IF;
    
    -- Assign role if user exists
    IF v_user_id IS NOT NULL THEN
        INSERT INTO model_has_roles (role_id, model_type, model_id)
        VALUES (v_role_id, 'App\\Models\\User', v_user_id)
        ON CONFLICT (role_id, model_id, model_type) DO NOTHING;
    END IF;
END $$;
*/

