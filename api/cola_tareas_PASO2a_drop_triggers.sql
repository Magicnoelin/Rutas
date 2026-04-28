-- ============================================================
-- PASO 2a: ELIMINAR TRIGGERS EXISTENTES (si los hay)
-- Ejecutar PRIMERO, antes de crear los triggers
-- ============================================================

DROP TRIGGER IF EXISTS trg_resource_stats_after_update;
DROP TRIGGER IF EXISTS trg_users_after_insert;
DROP TRIGGER IF EXISTS trg_accommodations_after_insert;
