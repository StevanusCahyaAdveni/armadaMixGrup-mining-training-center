-- ============================================
-- Insert User: HR Site
-- Role: HR Site
-- Password: password123 (hashed)
-- ============================================

INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `whatsapp_number`, `password`, `photo_profile`, `role`, `created_at`) 
VALUES (
    UUID(), 
    'HR Site Admin', 
    'hrsite', 
    'hrsite@gmail.com', 
    '08123456789', 
    '$2y$10$Jj.9jhKPy1u5AhnQpHOZF.Erjm2HiV3QBYpkdMSmyc0BYsipsvC2a', 
    NULL, 
    'HR Site', 
    NOW()
);
