ALTER TABLE employee_timesheets 
ADD COLUMN waktu_awal TIME NULL AFTER hm_akhir,
ADD COLUMN waktu_akhir TIME NULL AFTER waktu_awal,
ADD COLUMN overtime_rest_start TIME NULL AFTER overtime_end,
ADD COLUMN overtime_rest_end TIME NULL AFTER overtime_rest_start;

-- Update the rate for time-based incentive
UPDATE settings SET setting_value = '17000' WHERE setting_key = 'tarif_hm';
