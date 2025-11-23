/**
 * Check if a user has admin role
 * @param user - User object with roles or is_admin property
 * @returns boolean indicating if user is admin
 */
export function isAdmin(user: any): boolean {
  if (!user) return false;
  
  // Check is_admin property (set by backend)
  if (user.is_admin === true) return true;
  
  // Check for admin roles
  const adminRoles = [
    'Super Admin',
    'Financial Admin',
    'Moderator',
    'Support Agent',
    'Treasurer',
    'Secretary',
    'Auditor',
  ];
  
  if (user.roles && Array.isArray(user.roles)) {
    return user.roles.some((role: any) => 
      adminRoles.includes(role.name || role)
    );
  }
  
  return false;
}

