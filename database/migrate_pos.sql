-- =========================================================
-- FRESH SCENTS — MIGRATION: Uuzaji wa Moja kwa Moja Dukani (Walk-in Sales)
-- Endesha SQL hii MARA MOJA TU kwenye database yako iliyopo.
-- Fungua phpMyAdmin -> chagua database yako -> SQL tab -> bandika -> Go.
-- =========================================================

ALTER TABLE orders
  ADD COLUMN source ENUM('online','duka') NOT NULL DEFAULT 'online' AFTER delivery_type,
  ADD COLUMN served_by INT DEFAULT NULL AFTER source;

ALTER TABLE orders
  ADD CONSTRAINT fk_orders_served_by FOREIGN KEY (served_by) REFERENCES admins(id) ON DELETE SET NULL;

-- IMEKAMILIKA! Sasa unaweza kutumia ukurasa mpya "Uuzaji Dukani" kwenye
-- Admin Panel kurekodi mauzo ya wateja wanaokuja dukani moja kwa moja.
